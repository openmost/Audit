<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * Minimal YAML parser tailored to the Openmost audit checklist structure.
 * Supports: nested maps, dash lists, inline flow maps ({k: v, ...}), inline
 * flow lists ([a, b]), scalar quoting (single/double), block literals (| and >),
 * and comments.
 *
 * Not a general-purpose YAML implementation — only the subset used by
 * checklist.yaml is supported. When Matomo ships a YAML parser we should
 * swap this out.
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checklist;

class YamlParser
{
    /** @var string[] */
    private array $lines = [];
    private int $cursor = 0;

    public function parseFile(string $path): array
    {
        if (!is_readable($path)) {
            throw new \RuntimeException("YAML file not readable: {$path}");
        }
        return $this->parse((string) file_get_contents($path));
    }

    public function parse(string $yaml): array
    {
        $yaml = str_replace(["\r\n", "\r"], "\n", $yaml);
        $this->lines  = explode("\n", $yaml);
        $this->cursor = 0;
        return $this->parseBlock(0);
    }

    /**
     * Parse a block at the given indentation level. Returns either a map
     * (assoc array) or a list (sequential array), depending on the first
     * non-empty significant line.
     *
     * @return array<int|string, mixed>
     */
    private function parseBlock(int $indent): array
    {
        $result    = [];
        $isList    = null;

        while ($this->cursor < count($this->lines)) {
            $raw = $this->lines[$this->cursor];

            if ($this->isBlank($raw) || $this->isComment($raw)) {
                $this->cursor++;
                continue;
            }

            $lineIndent = $this->indentOf($raw);
            if ($lineIndent < $indent) {
                break;
            }
            if ($lineIndent > $indent) {
                // Should not happen — child parsing consumes deeper lines.
                $this->cursor++;
                continue;
            }

            $content = substr($raw, $lineIndent);

            // List item
            if (str_starts_with($content, '- ')) {
                $isList = true;
                $this->cursor++;
                $afterDash = substr($content, 2);

                // Inline flow scalar (map / sequence) as a whole item —
                // `- {id: foo, …}` or `- [a, b]`. Must be checked before
                // `looksLikeKeyValue()` because a flow map contains `:`
                // and would otherwise be misread as a key/value line.
                $trimmedAfter = trim($afterDash);
                if (
                    ($trimmedAfter !== '' && $trimmedAfter[0] === '{' && str_ends_with($trimmedAfter, '}'))
                    || ($trimmedAfter !== '' && $trimmedAfter[0] === '[' && str_ends_with($trimmedAfter, ']'))
                ) {
                    $result[] = $this->decodeScalar($trimmedAfter);
                    continue;
                }

                // The first item line can start a map inline: "- id: foo"
                if ($this->looksLikeKeyValue($afterDash)) {
                    // Build a "virtual" map whose first key is the stuff after the dash.
                    // We rewrite the current position: insert a synthetic line for
                    // consistent recursion, or parse inline and then attach siblings.
                    $itemMap = [];
                    $this->parseKeyValueInto($itemMap, $afterDash, $indent + 2);

                    // Continue reading siblings at indent+2
                    $childBlock = $this->parseBlock($indent + 2);
                    foreach ($childBlock as $k => $v) {
                        $itemMap[$k] = $v;
                    }
                    $result[] = $itemMap;
                    continue;
                }

                // "- " with a plain scalar / flow sequence / flow map
                if (trim($afterDash) === '') {
                    // empty dash — nested content below
                    $result[] = $this->parseBlock($indent + 2);
                } else {
                    $result[] = $this->decodeScalar(trim($afterDash));
                }
                continue;
            }

            // Key/value line
            if ($this->looksLikeKeyValue($content)) {
                $isList = false;
                $this->cursor++;
                $this->parseKeyValueInto($result, $content, $indent);
                continue;
            }

            // Unknown line: skip defensively.
            $this->cursor++;
        }

        return $result;
    }

    private function parseKeyValueInto(array &$target, string $content, int $indent): void
    {
        [$key, $rest] = $this->splitKeyValue($content);
        $key = $this->decodeScalar($key);

        if ($rest === '') {
            // Nested block follows
            $target[$key] = $this->parseBlock($indent + 2);
            return;
        }

        // Block scalar (| or >)
        if ($rest === '|' || $rest === '>' || str_starts_with($rest, '|') || str_starts_with($rest, '>')) {
            $style = $rest[0];
            $target[$key] = $this->readBlockScalar($indent + 2, $style);
            return;
        }

        // Inline flow
        $value = $this->decodeScalar($rest);
        $target[$key] = $value;
    }

    private function readBlockScalar(int $indent, string $style): string
    {
        $lines = [];
        while ($this->cursor < count($this->lines)) {
            $raw = $this->lines[$this->cursor];
            if ($this->isBlank($raw)) {
                $lines[] = '';
                $this->cursor++;
                continue;
            }
            $lineIndent = $this->indentOf($raw);
            if ($lineIndent < $indent) {
                break;
            }
            $lines[] = substr($raw, $indent);
            $this->cursor++;
        }

        // Drop trailing empties
        while (!empty($lines) && $lines[count($lines) - 1] === '') {
            array_pop($lines);
        }

        if ($style === '>') {
            // Folded: join paragraphs with spaces
            $text = '';
            foreach ($lines as $i => $line) {
                if ($line === '') {
                    $text .= "\n";
                } else {
                    $text .= ($i > 0 && $lines[$i - 1] !== '' ? ' ' : '') . $line;
                }
            }
            return trim($text) . "\n";
        }

        return implode("\n", $lines) . "\n";
    }

    private function splitKeyValue(string $content): array
    {
        // Respect quoted keys and flow scalars. Keys in our checklist are
        // always simple identifiers, so a simple split on first ": " / ":" is fine.
        $len      = strlen($content);
        $inQuote  = null;
        for ($i = 0; $i < $len; $i++) {
            $ch = $content[$i];
            if ($inQuote) {
                if ($ch === $inQuote) {
                    $inQuote = null;
                }
                continue;
            }
            if ($ch === '"' || $ch === "'") {
                $inQuote = $ch;
                continue;
            }
            if ($ch === ':') {
                $next = $content[$i + 1] ?? '';
                if ($next === '' || $next === ' ' || $next === "\t") {
                    return [trim(substr($content, 0, $i)), trim(substr($content, $i + 1))];
                }
            }
        }
        return [trim($content), ''];
    }

    private function looksLikeKeyValue(string $content): bool
    {
        [$key, $rest] = $this->splitKeyValue($content);
        return $key !== '' && ($rest !== '' || str_contains($content, ':'));
    }

    private function isBlank(string $line): bool
    {
        return trim($line) === '';
    }

    private function isComment(string $line): bool
    {
        $trimmed = ltrim($line);
        return $trimmed !== '' && $trimmed[0] === '#';
    }

    private function indentOf(string $line): int
    {
        $i = 0;
        $n = strlen($line);
        while ($i < $n && $line[$i] === ' ') {
            $i++;
        }
        return $i;
    }

    /**
     * Decode a scalar value: quoted strings, numbers, booleans, null,
     * inline flow sequences/maps.
     *
     * @return mixed
     */
    private function decodeScalar(string $value)
    {
        $value = $this->stripInlineComment($value);
        $value = trim($value);

        if ($value === '' || $value === '~' || strcasecmp($value, 'null') === 0) {
            return null;
        }
        if (strcasecmp($value, 'true') === 0)  return true;
        if (strcasecmp($value, 'false') === 0) return false;

        // Quoted
        $len = strlen($value);
        if ($len >= 2) {
            $first = $value[0];
            $last  = $value[$len - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $inner = substr($value, 1, -1);
                if ($first === '"') {
                    // Handle basic escapes
                    $inner = str_replace(['\\"', '\\\\', '\\n', '\\t'], ['"', '\\', "\n", "\t"], $inner);
                }
                return $inner;
            }
        }

        // Flow sequence [a, b, c]
        if ($len >= 2 && $value[0] === '[' && $value[$len - 1] === ']') {
            return $this->parseFlowSequence(substr($value, 1, -1));
        }
        // Flow map {k: v, ...}
        if ($len >= 2 && $value[0] === '{' && $value[$len - 1] === '}') {
            return $this->parseFlowMap(substr($value, 1, -1));
        }

        // Numbers
        if (preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }
        if (preg_match('/^-?\d+\.\d+$/', $value)) {
            return (float) $value;
        }

        return $value;
    }

    /**
     * Strip trailing `# comment` from an unquoted scalar.
     */
    private function stripInlineComment(string $value): string
    {
        $len     = strlen($value);
        $inQuote = null;
        $depth   = 0;
        for ($i = 0; $i < $len; $i++) {
            $ch = $value[$i];
            if ($inQuote) {
                if ($ch === $inQuote) {
                    $inQuote = null;
                }
                continue;
            }
            if ($ch === '"' || $ch === "'") {
                $inQuote = $ch;
                continue;
            }
            if ($ch === '[' || $ch === '{') $depth++;
            if ($ch === ']' || $ch === '}') $depth--;
            if ($depth === 0 && $ch === '#' && $i > 0 && $value[$i - 1] === ' ') {
                return substr($value, 0, $i);
            }
        }
        return $value;
    }

    private function parseFlowSequence(string $content): array
    {
        $items = $this->splitFlow($content);
        return array_map(fn($v) => $this->decodeScalar(trim($v)), $items);
    }

    private function parseFlowMap(string $content): array
    {
        $items  = $this->splitFlow($content);
        $result = [];
        foreach ($items as $entry) {
            if (trim($entry) === '') {
                continue;
            }
            [$k, $v] = $this->splitKeyValue($entry);
            $result[$this->decodeScalar($k)] = $this->decodeScalar($v);
        }
        return $result;
    }

    /**
     * Split a flow expression on top-level commas, respecting quotes and
     * nested brackets.
     *
     * @return string[]
     */
    private function splitFlow(string $content): array
    {
        $items   = [];
        $buf     = '';
        $inQuote = null;
        $depth   = 0;
        $len     = strlen($content);
        for ($i = 0; $i < $len; $i++) {
            $ch = $content[$i];
            if ($inQuote) {
                $buf .= $ch;
                if ($ch === $inQuote) {
                    $inQuote = null;
                }
                continue;
            }
            if ($ch === '"' || $ch === "'") {
                $inQuote = $ch;
                $buf .= $ch;
                continue;
            }
            if ($ch === '[' || $ch === '{') {
                $depth++;
                $buf .= $ch;
                continue;
            }
            if ($ch === ']' || $ch === '}') {
                $depth--;
                $buf .= $ch;
                continue;
            }
            if ($ch === ',' && $depth === 0) {
                $items[] = $buf;
                $buf = '';
                continue;
            }
            $buf .= $ch;
        }
        if ($buf !== '') {
            $items[] = $buf;
        }
        return $items;
    }
}
