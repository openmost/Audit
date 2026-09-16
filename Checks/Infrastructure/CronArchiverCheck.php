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
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class CronArchiverCheck extends AbstractCheck
{
    private const WARN_AFTER_HOURS = 24;
    private const FAIL_AFTER_HOURS = 48;

    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Option::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Option is not available in this context.'));
        }

        $optionKey = class_exists(\Piwik\CronArchive::class) && defined(\Piwik\CronArchive::class . '::OPTION_ARCHIVING_FINISHED_TS')
            ? \Piwik\CronArchive::OPTION_ARCHIVING_FINISHED_TS
            : 'LastCompletedFullArchiving';

        try {
            $raw = \Piwik\Option::get($optionKey);
        } catch (\Throwable $e) {
            return $this->skip($item, detail: $this->t($item, 'read-error', ['error' => $e->getMessage()],
                'Could not read archiving status: ' . $e->getMessage()));
        }

        $timestamp = (int) $raw;
        if ($timestamp <= 0) {
            return $this->fail($item,
                detail: $this->t($item, 'never', [], 'No successful archiving run has been recorded. The core:archive CRON is likely not running.'),
                currentValue: 'never',
                expectedValue: 'within the last 24h'
            );
        }

        $ageHours = (time() - $timestamp) / 3600;
        $ageLabel = $this->formatAge($ageHours);
        $lastRun  = gmdate('Y-m-d\TH:i:s\Z', $timestamp);
        $vars     = ['age' => $ageLabel, 'lastRun' => $lastRun];

        if ($ageHours >= self::FAIL_AFTER_HOURS) {
            return $this->fail($item,
                detail: $this->t($item, 'stalled', $vars, "Last successful archiving was {$ageLabel} ago ({$lastRun}). The CRON is stalled."),
                currentValue: $lastRun,
                expectedValue: 'within the last 24h'
            );
        }

        if ($ageHours >= self::WARN_AFTER_HOURS) {
            return $this->warn($item,
                detail: $this->t($item, 'lagging', $vars, "Last successful archiving was {$ageLabel} ago ({$lastRun})."),
                currentValue: $lastRun,
                expectedValue: 'within the last 24h'
            );
        }

        return $this->pass($item,
            detail: $this->t($item, 'pass', $vars, "Last successful archiving was {$ageLabel} ago ({$lastRun})."),
            currentValue: $lastRun,
            expectedValue: 'within the last 24h'
        );
    }

    private function formatAge(float $hours): string
    {
        if ($hours < 1)  return round($hours * 60) . ' min';
        if ($hours < 48) return round($hours, 1) . ' h';
        return round($hours / 24, 1) . ' d';
    }
}
