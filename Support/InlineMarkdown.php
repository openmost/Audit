<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Support;

/**
 * Minimal inline-Markdown → HTML converter used when serialising check
 * recommendations to the Vue front-end. It intentionally covers only
 * the subset of Markdown used in `checklist.yaml` templates so we do not
 * need to ship a full Markdown parser.
 *
 * Supported constructs:
 *   - ```lang … ```           → <pre><code class="language-lang">…</code></pre>
 *   - **strong**              → <strong>strong</strong>
 *   - *italic* / _italic_     → <em>italic</em>
 *   - `inline code`           → <code>inline code</code>
 *   - Blank lines             → paragraph separation
 *   - Single newlines         → <br>
 *
 * Input is HTML-escaped first, so the produced markup only contains the
 * tags introduced here. Combined with Matomo's `window.vueSanitize`
 * call on the front-end, the payload stays XSS-safe even though the
 * source YAML is trusted.
 */
class InlineMarkdown
{
    public static function toHtml(string $raw): string
    {
        // Extract fenced code blocks BEFORE escaping so their content
        // (which often contains `*`, `_`, backticks…) is not mangled by
        // the inline pass. We swap them for unique placeholders, then
        // re-inject the fully-rendered <pre><code> at the end.
        $fences = [];
        $raw    = preg_replace_callback(
            '/```([a-zA-Z0-9_-]*)\s*\n(.*?)\n```/s',
            static function (array $m) use (&$fences): string {
                $lang  = $m[1] ?? '';
                $body  = $m[2] ?? '';
                $token = '__AUDIT_FENCE_' . count($fences) . '__';
                $fences[$token] = sprintf(
                    '<pre><code%s>%s</code></pre>',
                    $lang !== '' ? ' class="language-' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '"' : '',
                    htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8')
                );
                return $token;
            },
            $raw
        ) ?? $raw;

        $text = htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

        // Inline code — must run before emphasis to avoid clashing on *
        // inside snippets.
        $text = preg_replace_callback(
            '/`([^`\n]+)`/',
            static fn(array $m) => '<code>' . $m[1] . '</code>',
            $text
        ) ?? $text;

        // Bold: **text** — must run before single-star italic.
        $text = preg_replace(
            '/\*\*([^*\n]+?)\*\*/',
            '<strong>$1</strong>',
            $text
        ) ?? $text;

        // Italic: *text* (but not **text**) and _text_.
        $text = preg_replace(
            '/(?<![\*_\w])\*([^*\n]+?)\*(?![\*_\w])/',
            '<em>$1</em>',
            $text
        ) ?? $text;
        $text = preg_replace(
            '/(?<![_\w])_([^_\n]+?)_(?![_\w])/',
            '<em>$1</em>',
            $text
        ) ?? $text;

        // Paragraphs: split on blank lines, then either:
        //   - emit the fence placeholder verbatim (no surrounding <p>),
        //   - render the block as a <ul> if every non-empty line starts
        //     with `- ` or `* `, or
        //   - wrap as a regular paragraph with soft line-breaks.
        $paragraphs = preg_split("/\n\s*\n/", trim($text)) ?: [];
        $paragraphs = array_map(
            static function (string $p) use ($fences): string {
                $trimmed = trim($p);
                if (isset($fences[$trimmed])) {
                    return $fences[$trimmed];
                }

                $lines     = preg_split("/\n/", $trimmed) ?: [];
                $itemLines = [];
                $isList    = !empty($lines);
                foreach ($lines as $line) {
                    if (!preg_match('/^\s*[-*]\s+(.+)$/', $line, $m)) {
                        $isList = false;
                        break;
                    }
                    $itemLines[] = trim($m[1]);
                }
                if ($isList && !empty($itemLines)) {
                    return '<ul>' . implode('', array_map(
                        static fn(string $li) => '<li>' . $li . '</li>',
                        $itemLines
                    )) . '</ul>';
                }

                return '<p>' . nl2br($trimmed, false) . '</p>';
            },
            $paragraphs
        );

        $html = implode("\n", $paragraphs);

        // Inject any remaining placeholders (e.g. fence inside a paragraph).
        if (!empty($fences)) {
            $html = strtr($html, $fences);
        }

        return $html;
    }
}
