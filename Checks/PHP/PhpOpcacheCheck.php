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

        return $this->evaluate(
            $item,
            @opcache_get_status(false),
            self::enabledSetting(),
            self::configuredPoolMb()
        );
    }

    /**
     * @param array|false $status          Result of opcache_get_status(false).
     * @param bool        $enabledSetting  Value of the opcache.enable setting.
     * @param int|null    $configuredPoolMb Value of opcache.memory_consumption, in MB.
     */
    public function evaluate(ChecklistItem $item, $status, bool $enabledSetting, ?int $configuredPoolMb): CheckResult
    {
        if (!is_array($status)) {
            // opcache.restrict_api makes opcache_get_status() return false while OPcache runs.
            if ($enabledSetting) {
                return $this->skip($item,
                    detail: $this->t($item, 'status-unavailable', [], 'OPcache is enabled, but its status cannot be read (check opcache.restrict_api).'),
                    currentValue: 'unknown'
                );
            }
            return $this->fail($item,
                detail: $this->t($item, 'disabled', [], 'OPcache is installed but disabled.'),
                currentValue: 'disabled'
            );
        }

        if (empty($status['opcache_enabled'])) {
            return $this->fail($item,
                detail: $this->t($item, 'disabled', [], 'OPcache is installed but disabled.'),
                currentValue: 'disabled'
            );
        }

        $memory = self::readMemoryUsage($status);
        if ($memory === null) {
            // Some PHP builds report negative counters (seen on PHP 8.5 after OPcache restarts).
            return $this->evaluateConfiguredPool($item, $configuredPoolMb);
        }

        [$usedMb, $totalMb] = $memory;
        $vars = ['total' => $totalMb, 'used' => $usedMb];

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

    private function evaluateConfiguredPool(ChecklistItem $item, ?int $configuredPoolMb): CheckResult
    {
        if ($configuredPoolMb === null) {
            return $this->skip($item,
                detail: $this->t($item, 'unknown', [], 'OPcache is enabled, but the size of its memory pool cannot be determined.'),
                currentValue: 'unknown'
            );
        }

        $vars = ['total' => $configuredPoolMb];

        if ($configuredPoolMb < self::MIN_POOL_MB) {
            return $this->warn($item,
                detail: $this->t($item, 'pool-too-small', $vars, "OPcache is active but the memory pool is too small ({$configuredPoolMb} MB)."),
                currentValue: "{$configuredPoolMb}M",
                expectedValue: '>= 256M'
            );
        }

        return $this->pass($item,
            detail: $this->t($item, 'pass-pool', $vars, "OPcache enabled with a {$configuredPoolMb} MB memory pool. PHP does not report a usable memory usage."),
            currentValue: "{$configuredPoolMb}M total",
            expectedValue: '>= 256M'
        );
    }

    /**
     * @return array{0: int, 1: int}|null Used and total memory in MB, null when the counters are missing or inconsistent.
     */
    private static function readMemoryUsage(array $status): ?array
    {
        $usage = $status['memory_usage'] ?? null;
        if (!is_array($usage)) {
            return null;
        }

        $counters = [];
        foreach (['used_memory', 'free_memory', 'wasted_memory'] as $key) {
            if (!isset($usage[$key]) || !is_numeric($usage[$key]) || $usage[$key] < 0) {
                return null;
            }
            $counters[$key] = (int) $usage[$key];
        }

        $total = array_sum($counters);
        if ($total <= 0) {
            return null;
        }

        return [
            (int) round($counters['used_memory'] / 1024 / 1024),
            (int) round($total / 1024 / 1024),
        ];
    }

    private static function enabledSetting(): bool
    {
        $setting = PHP_SAPI === 'cli' ? 'opcache.enable_cli' : 'opcache.enable';
        return filter_var(ini_get($setting), FILTER_VALIDATE_BOOLEAN);
    }

    private static function configuredPoolMb(): ?int
    {
        $value = ini_get('opcache.memory_consumption');
        if ($value === false || !is_numeric($value) || (int) $value <= 0) {
            return null;
        }
        return (int) $value;
    }
}
