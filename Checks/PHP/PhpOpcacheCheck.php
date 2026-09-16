<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks\PHP;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class PhpOpcacheCheck extends AbstractCheck
{
    private const MIN_POOL_MB = 256;

    public function execute(ChecklistItem $item): CheckResult
    {
        if (!function_exists('opcache_get_status')) {
            return $this->fail($item,
                detail: $this->t($item, 'missing', [], 'OPcache extension is not available.'),
                currentValue: 'disabled'
            );
        }

        $status = @opcache_get_status(false);
        if ($status === false || empty($status['opcache_enabled'])) {
            return $this->fail($item,
                detail: $this->t($item, 'disabled', [], 'OPcache is installed but disabled.'),
                currentValue: 'disabled'
            );
        }

        $used   = (int) ($status['memory_usage']['used_memory']   ?? 0);
        $free   = (int) ($status['memory_usage']['free_memory']   ?? 0);
        $wasted = (int) ($status['memory_usage']['wasted_memory'] ?? 0);
        $totalMb = (int) round(($used + $free + $wasted) / 1024 / 1024);
        $usedMb  = (int) round($used / 1024 / 1024);
        $vars    = ['total' => $totalMb, 'used' => $usedMb];

        if ($totalMb < self::MIN_POOL_MB) {
            return $this->warn($item,
                detail: $this->t($item, 'pool-too-small', $vars, "OPcache is active but the memory pool is too small ({$totalMb} MB)."),
                currentValue: "{$totalMb}M",
                expectedValue: '>= 256M'
            );
        }

        return $this->pass($item,
            detail: $this->t($item, 'pass', $vars, "OPcache enabled: {$usedMb} MB used out of {$totalMb} MB."),
            currentValue: "{$usedMb}M used / {$totalMb}M total",
            expectedValue: '>= 256M'
        );
    }
}
