<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Export;

use Piwik\Plugins\Audit\Audit;
use Piwik\Plugins\Audit\Checks\AuditReport;
use Piwik\Plugins\Audit\Checks\CheckResult;

/**
 * Renders an AuditReport as a Markdown document that the consultant can
 * paste directly into a Word / Notion / Confluence report.
 *
 * Structure:
 *   # Matomo audit report
 *   instance metadata + summary table
 *   ## <category label>
 *   ### [status] <title> — <severity>
 *   - id, detail
 *   - current/expected
 *   - recommendation + code snippet
 *   - references
 */
class MarkdownExporter
{
    private const STATUS_EMOJI = [
        CheckResult::STATUS_PASS => '✅',
        CheckResult::STATUS_FAIL => '❌',
        CheckResult::STATUS_WARN => '⚠️',
        CheckResult::STATUS_SKIP => '⏭️',
        CheckResult::STATUS_PREMIUM => '🔒',
    ];

    private const STATUS_LABEL_PLAIN = [
        CheckResult::STATUS_PASS => '[PASS]',
        CheckResult::STATUS_FAIL => '[FAIL]',
        CheckResult::STATUS_WARN => '[WARN]',
        CheckResult::STATUS_SKIP => '[SKIP]',
        CheckResult::STATUS_PREMIUM => '[PREMIUM]',
    ];

    /** @var array<string, string> */
    private array $categoryLabels;

    private bool $plain;

    /**
     * @param array<string, string> $categoryLabels Map id → human label (from checklist metadata).
     * @param bool                  $plain          When true, emoji badges are replaced with plain-text markers
     *                                               (`[PASS]`, `[FAIL]`, `[WARN]`, `[SKIP]`) so the output
     *                                               diffs cleanly and survives ASCII-only pipelines.
     */
    public function __construct(array $categoryLabels = [], bool $plain = false)
    {
        $this->categoryLabels = $categoryLabels;
        $this->plain          = $plain;
    }

    private function badge(string $status): string
    {
        if ($this->plain) {
            return self::STATUS_LABEL_PLAIN[$status] ?? '[' . strtoupper($status) . ']';
        }
        return self::STATUS_EMOJI[$status] ?? '';
    }

    public function render(AuditReport $report): string
    {
        $md  = "# Matomo audit report\n\n";
        $md .= "| Field | Value |\n|---|---|\n";
        $md .= "| Generated at | {$report->generatedAt} |\n";
        $md .= "| Matomo version | {$report->matomoVersion} |\n";
        $md .= "| PHP version | {$report->phpVersion} |\n";
        $md .= "| Plugin version | {$report->pluginVersion} |\n\n";

        $summary = $report->summary();
        $md .= "## Summary\n\n";
        $md .= "| Status | Count |\n|---|---:|\n";
        $md .= "| Total | {$summary['total']} |\n";
        $md .= '| ' . $this->badge(CheckResult::STATUS_PASS) . ' Pass | ' . $summary['pass'] . " |\n";
        $md .= '| ' . $this->badge(CheckResult::STATUS_FAIL) . ' Fail | ' . $summary['fail'] . " |\n";
        $md .= '| ' . $this->badge(CheckResult::STATUS_WARN) . ' Warn | ' . $summary['warn'] . " |\n";
        $md .= '| ' . $this->badge(CheckResult::STATUS_SKIP) . ' Skip | ' . $summary['skip'] . " |\n";
        $md .= '| ' . $this->badge(CheckResult::STATUS_PREMIUM) . ' Premium (not run) | ' . $summary['premium'] . " |\n\n";
        $md .= "**Severity breakdown:** ";
        $md .= "{$summary['critical']} critical · {$summary['high']} high · {$summary['medium']} medium · "
             . "{$summary['low']} low · {$summary['info']} info\n\n";

        /** @var array<string, CheckResult[]> $groups */
        $groups  = [];
        $premium = [];
        foreach ($report->results as $r) {
            if ($r->status === CheckResult::STATUS_PREMIUM) {
                $premium[$r->category][] = $r;
                continue;
            }
            $groups[$r->category][] = $r;
        }
        ksort($groups);

        foreach ($groups as $category => $results) {
            $label = $this->categoryLabels[$category] ?? $category;
            $md .= "## {$label}\n\n";

            usort($results, fn(CheckResult $a, CheckResult $b) => $this->severityWeight($a->severity) <=> $this->severityWeight($b->severity));

            foreach ($results as $r) {
                $md .= $this->renderFinding($r);
            }
        }

        if (!empty($premium)) {
            ksort($premium);
            $md .= "## Premium checks (not run)\n\n";
            $md .= 'These checks are available in AuditPremium: <' . Audit::PREMIUM_URL . ">\n\n";
            foreach ($premium as $category => $results) {
                $label = $this->categoryLabels[$category] ?? $category;
                $md .= "### {$label}\n\n";
                foreach ($results as $r) {
                    $md .= "- {$r->title} (`{$r->itemId}`, {$r->severity})\n";
                }
                $md .= "\n";
            }
        }

        return $md;
    }

    private function renderFinding(CheckResult $r): string
    {
        $badge = $this->badge($r->status);
        $md  = "### {$badge} {$r->title}\n\n";
        $md .= "- **ID:** `{$r->itemId}`\n";
        $md .= "- **Status:** {$r->status}  **·**  **Severity:** {$r->severity}\n";
        if ($r->detail !== '') {
            $md .= "- **Detail:** " . $this->escapeInline($r->detail) . "\n";
        }
        if ($r->currentValue !== null && $r->currentValue !== '') {
            $md .= "- **Current value:** `" . $this->escapeCode($r->currentValue) . "`\n";
        }
        if ($r->expectedValue !== null && $r->expectedValue !== '') {
            $md .= "- **Expected:** `" . $this->escapeCode($r->expectedValue) . "`\n";
        }
        $md .= "\n";

        if ($r->recommendation !== null && $r->recommendation !== '') {
            $md .= "**Recommendation:**\n\n";
            $md .= $this->quoteBlock($r->recommendation) . "\n\n";
        }
        if ($r->codeSnippet !== null && $r->codeSnippet !== '') {
            $md .= "```ini\n" . rtrim($r->codeSnippet) . "\n```\n\n";
        }
        if (!empty($r->references)) {
            $md .= "**References:** ";
            $md .= implode(' · ', array_map(static fn($u) => "<{$u}>", $r->references));
            $md .= "\n\n";
        }

        $md .= "---\n\n";
        return $md;
    }

    private function severityWeight(string $severity): int
    {
        return match ($severity) {
            CheckResult::SEV_CRITICAL => 0,
            CheckResult::SEV_HIGH     => 1,
            CheckResult::SEV_MEDIUM   => 2,
            CheckResult::SEV_LOW      => 3,
            CheckResult::SEV_INFO     => 4,
            default                   => 9,
        };
    }

    private function escapeInline(string $s): string
    {
        // We don't want a finding detail to break the list item layout,
        // so collapse any newlines to spaces.
        $s = preg_replace("/\r?\n+/", ' ', $s) ?? $s;
        return trim($s);
    }

    private function escapeCode(string $s): string
    {
        return str_replace('`', "\u{200B}`", $s);
    }

    private function quoteBlock(string $s): string
    {
        $lines = preg_split("/\r?\n/", trim($s)) ?: [];
        return implode("\n", array_map(static fn($l) => '> ' . $l, $lines));
    }
}
