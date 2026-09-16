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

class TrackerCacheShortCheck extends AbstractCheck
{
    /**
     * Matomo's default is 300 s. Anything above 15 min makes site-level
     * config changes (excluded IPs, new goals, currency) take hours to
     * propagate to the tracker endpoint.
     */
    private const WARN_ABOVE_SECONDS = 900;

    public function execute(ChecklistItem $item): CheckResult
    {
        $tracker = $this->readConfigSection('Tracker');
        if ($tracker === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }

        $ttl = (int) ($tracker['tracker_cache_file_ttl'] ?? 300);

        if ($ttl <= 0) {
            return $this->warn($item,
                detail: $this->t($item, 'zero', [],
                    'tracker_cache_file_ttl = 0 disables the tracker cache entirely. Every hit re-reads site config.'),
                currentValue: (string) $ttl,
                expectedValue: '60–900 s'
            );
        }

        if ($ttl > self::WARN_ABOVE_SECONDS) {
            return $this->warn($item,
                detail: $this->t($item, 'too-high', ['current' => $ttl],
                    "tracker_cache_file_ttl = {$ttl} s: site-level config changes will take that long to reach the tracker."),
                currentValue: $ttl . ' s',
                expectedValue: '<= 900 s'
            );
        }

        return $this->pass($item,
            detail: $this->t($item, 'pass', ['current' => $ttl],
                "tracker_cache_file_ttl = {$ttl} s (within recommended range)."),
            currentValue: $ttl . ' s',
            expectedValue: '<= 900 s'
        );
    }
}
