<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks;

use Piwik\Plugins\Audit\Checklist\ChecklistItem;
use Piwik\Plugins\Audit\Context\InstanceMetrics;
use Piwik\Plugins\Audit\Support\CheckTranslator;

abstract class AbstractCheck implements CheckInterface
{
    protected ?InstanceMetrics $metrics = null;

    public function setMetrics(InstanceMetrics $metrics): void
    {
        $this->metrics = $metrics;
    }

    abstract public function execute(ChecklistItem $item): CheckResult;

    /**
     * Short alias for `CheckTranslator::detail()`. Usage:
     *
     *   return $this->warn($item,
     *       detail: $this->t($item, 'too-low', ['current' => $seconds], 'fallback message'),
     *       ...
     *   );
     *
     * Looks up `Audit_<ItemSlug>Detail<VariantPascal>` in lang/*.json and
     * substitutes `{placeholder}` tokens with the provided vars. Falls
     * back to the last argument when no translation is registered.
     */
    protected function t(ChecklistItem $item, string $variant, array $vars = [], string $fallback = ''): string
    {
        return CheckTranslator::detail($item, $variant, $vars, $fallback);
    }

    protected function pass(
        ChecklistItem $item,
        string $detail = '',
        ?string $currentValue = null,
        ?string $expectedValue = null,
        array $templateVars = []
    ): CheckResult {
        return $this->build($item, CheckResult::STATUS_PASS, $detail, $currentValue, $expectedValue, $templateVars);
    }

    protected function fail(
        ChecklistItem $item,
        string $detail = '',
        ?string $currentValue = null,
        ?string $expectedValue = null,
        array $templateVars = []
    ): CheckResult {
        return $this->build($item, CheckResult::STATUS_FAIL, $detail, $currentValue, $expectedValue, $templateVars);
    }

    protected function warn(
        ChecklistItem $item,
        string $detail = '',
        ?string $currentValue = null,
        ?string $expectedValue = null,
        array $templateVars = []
    ): CheckResult {
        return $this->build($item, CheckResult::STATUS_WARN, $detail, $currentValue, $expectedValue, $templateVars);
    }

    protected function skip(
        ChecklistItem $item,
        string $detail = '',
        ?string $currentValue = null
    ): CheckResult {
        return $this->build($item, CheckResult::STATUS_SKIP, $detail, $currentValue, null, []);
    }

    private function build(
        ChecklistItem $item,
        string $status,
        string $detail,
        ?string $currentValue,
        ?string $expectedValue,
        array $templateVars
    ): CheckResult {
        $shouldRender = in_array($status, [CheckResult::STATUS_FAIL, CheckResult::STATUS_WARN], true);

        // Title + template come from lang/*.json when available, falling
        // back to the raw YAML values for items whose translation has
        // not landed yet.
        $title    = CheckTranslator::title($item);
        $template = CheckTranslator::template($item);

        $recommendation = null;
        if ($shouldRender && $template !== null) {
            $recommendation = $this->renderTemplate($template, $templateVars + [
                'current'  => (string) ($currentValue ?? ''),
                'expected' => (string) ($expectedValue ?? ''),
            ]);
        }

        $codeSnippet = $shouldRender ? $item->code : null;

        return new CheckResult(
            itemId: $item->id,
            title: $title,
            category: $item->category,
            status: $status,
            severity: $item->severity,
            detail: $detail,
            currentValue: $currentValue,
            expectedValue: $expectedValue,
            recommendation: $recommendation,
            codeSnippet: $codeSnippet,
            references: $item->refs
        );
    }

    /**
     * Expand jinja-like {placeholder} references in the checklist template.
     *
     * @param array<string, scalar|null> $vars
     */
    private function renderTemplate(string $template, array $vars): string
    {
        $pairs = [];
        foreach ($vars as $k => $v) {
            $pairs['{' . $k . '}'] = (string) ($v ?? '');
        }
        return trim(strtr($template, $pairs));
    }

    /**
     * Read a `[section]` block from `config.ini.php` defensively. Returns
     * `null` when the Matomo Config class is unavailable or when reading
     * the section throws — callers should then `skip()` with a sensible
     * message. This keeps ConfigFile checks aligned with the try/catch
     * pattern used by the Database layer.
     *
     * @return array<string, mixed>|null
     */
    protected function readConfigSection(string $section = 'General'): ?array
    {
        if (!class_exists(\Piwik\Config::class)) {
            return null;
        }
        try {
            $config = \Piwik\Config::getInstance();
            $value  = $config->{$section} ?? null;
            return is_array($value) ? $value : [];
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function normalizeSize(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '-1') {
            return null;
        }
        if (preg_match('/^(\d+)\s*([KMGkmg]?)$/', $raw, $m)) {
            $n    = (int) $m[1];
            $unit = strtoupper($m[2] ?? '');
            return match ($unit) {
                'G'     => $n * 1024 * 1024 * 1024,
                'M'     => $n * 1024 * 1024,
                'K'     => $n * 1024,
                default => $n,
            };
        }
        return null;
    }
}
