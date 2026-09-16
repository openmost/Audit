<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks\Database;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class MysqlSlowQueryLogCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Db::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Db is not available in this context.'));
        }

        try {
            $row = \Piwik\Db::fetchRow("SHOW VARIABLES LIKE 'slow_query_log'");
        } catch (\Throwable $e) {
            return $this->skip($item, detail: $this->t($item, 'read-error', ['error' => $e->getMessage()],
                'Could not query slow_query_log: ' . $e->getMessage()));
        }

        $value     = (string) ($row['Value'] ?? '');
        $isEnabled = in_array(strtoupper($value), ['ON', '1'], true);
        $vars      = ['current' => $value ?: 'OFF'];

        if ($isEnabled) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars, 'Slow query log is enabled.'),
                currentValue: $value,
                expectedValue: 'ON'
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'disabled', $vars, 'Slow query log is disabled.'),
            currentValue: $value ?: 'OFF',
            expectedValue: 'ON'
        );
    }
}
