<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks\Infrastructure;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checks\MetricsAwareInterface;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class DbSizeCheck extends AbstractCheck implements MetricsAwareInterface
{
    public function execute(ChecklistItem $item): CheckResult
    {
        if ($this->metrics === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Instance metrics service is not available.'));
        }

        if ($this->metrics->getDbSizeBytes() <= 0) {
            return $this->skip($item, detail: $this->t($item, 'no-data', [], 'Could not measure database size.'));
        }

        $vars = [
            'gb'        => $this->metrics->getDbSizeGb(),
            'largestMb' => $this->metrics->getLargestTableMb(),
        ];

        $variant = $this->metrics->isLargeDb() ? 'large' : 'pass';
        $fallback = "Matomo schema occupies **{$vars['gb']} GB** (largest single table: {$vars['largestMb']} MB).";
        if ($variant === 'large') {
            $fallback .= ' This counts as a *large* installation: adjacent sizing checks are tightened accordingly.';
        }

        return $this->pass($item,
            detail: $this->t($item, $variant, $vars, $fallback),
            currentValue: "{$vars['gb']} GB",
            expectedValue: 'informational'
        );
    }
}
