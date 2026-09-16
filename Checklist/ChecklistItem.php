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

/**
 * Value object representing a single item of the audit checklist.
 */
class ChecklistItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $category,
        public readonly string $title,
        public readonly string $severity,
        public readonly array $methods = [],
        public readonly array $rules = [],
        public readonly ?string $template = null,
        public readonly ?string $code = null,
        public readonly array $refs = [],
        public readonly bool $premium = false
    ) {
    }

    public static function fromArray(array $raw): self
    {
        $reserved = ['id', 'cat', 'title', 'severity', 'methods', 'template', 'code', 'refs', 'premium'];
        $rules    = [];
        foreach ($raw as $key => $value) {
            if (!in_array($key, $reserved, true)) {
                $rules[$key] = $value;
            }
        }

        return new self(
            id: (string) ($raw['id'] ?? ''),
            category: (string) ($raw['cat'] ?? ''),
            title: (string) ($raw['title'] ?? ''),
            severity: (string) ($raw['severity'] ?? 'info'),
            methods: (array) ($raw['methods'] ?? []),
            rules: $rules,
            template: isset($raw['template']) ? (string) $raw['template'] : null,
            code: isset($raw['code']) ? (string) $raw['code'] : null,
            refs: (array) ($raw['refs'] ?? []),
            premium: ($raw['premium'] ?? false) === true
        );
    }

    public function getRule(string $key): ?array
    {
        $value = $this->rules[$key] ?? null;
        return is_array($value) ? $value : null;
    }
}
