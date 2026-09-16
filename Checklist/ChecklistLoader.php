<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checklist;

class ChecklistLoader
{
    /**
     * Highest schema version this loader understands. Raising the version
     * in `checklist.yaml` past this number triggers a hard failure so that
     * a new schema never runs through an outdated parser silently.
     */
    public const SUPPORTED_VERSION = 1;

    private const VALID_SEVERITIES = ['critical', 'high', 'medium', 'low', 'info'];

    private string $path;
    private ?array $raw = null;

    /** @var string[] */
    private array $warnings = [];

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? __DIR__ . '/checklist.yaml';
    }

    /**
     * @return ChecklistItem[]
     */
    public function loadItems(): array
    {
        $data  = $this->loadRaw();
        $items = [];
        foreach (($data['items'] ?? []) as $raw) {
            if (!$this->validateItem($raw)) {
                continue;
            }
            $items[] = ChecklistItem::fromArray($raw);
        }
        return $items;
    }

    /**
     * Warnings collected during the last `loadItems()` call: malformed or
     * skipped items. Exposed for unit tests and for diagnostics.
     *
     * @return string[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    private function validateItem(mixed $raw): bool
    {
        if (!is_array($raw)) {
            $this->warnings[] = 'Skipped item: not a map.';
            return false;
        }
        if (empty($raw['id']) || !is_string($raw['id'])) {
            $this->warnings[] = 'Skipped item: missing or non-string id.';
            return false;
        }
        $id = $raw['id'];
        if (empty($raw['cat']) || !is_string($raw['cat'])) {
            $this->warnings[] = "Skipped item {$id}: missing or non-string cat.";
            return false;
        }
        $severity = $raw['severity'] ?? null;
        if (!in_array($severity, self::VALID_SEVERITIES, true)) {
            $this->warnings[] = "Skipped item {$id}: invalid severity '" . (is_scalar($severity) ? $severity : gettype($severity)) . "'.";
            return false;
        }
        return true;
    }

    /**
     * Return raw checklist metadata (categories, severities, methods, etc.)
     * useful for the front-end.
     */
    public function loadMetadata(): array
    {
        $data = $this->loadRaw();
        return [
            'version'    => $data['version']    ?? null,
            'metadata'   => $data['metadata']   ?? [],
            'methods'    => $data['methods']    ?? [],
            'categories' => $data['categories'] ?? [],
            'severities' => $data['severities'] ?? [],
        ];
    }

    public function findById(string $id): ?ChecklistItem
    {
        foreach ($this->loadItems() as $item) {
            if ($item->id === $id) {
                return $item;
            }
        }
        return null;
    }

    private function loadRaw(): array
    {
        if ($this->raw === null) {
            $parser    = new YamlParser();
            $this->raw = $parser->parseFile($this->path);
            $this->assertSchemaCompatible($this->raw);
        }
        return $this->raw;
    }

    private function assertSchemaCompatible(array $raw): void
    {
        $version = $raw['version'] ?? null;
        if ($version === null) {
            return; // legacy files without a version header are tolerated
        }
        if (!is_int($version) && !ctype_digit((string) $version)) {
            throw new \RuntimeException(sprintf(
                'Checklist %s: invalid "version" header (%s). Must be an integer.',
                $this->path,
                is_scalar($version) ? $version : gettype($version)
            ));
        }
        $version = (int) $version;
        if ($version > self::SUPPORTED_VERSION) {
            throw new \RuntimeException(sprintf(
                'Checklist %s declares schema version %d but this loader only supports up to %d. Upgrade the Audit plugin.',
                $this->path,
                $version,
                self::SUPPORTED_VERSION
            ));
        }
    }
}
