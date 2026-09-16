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

class MysqlInnodbFlushLogCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Db::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Db is not available in this context.'));
        }

        try {
            $row = \Piwik\Db::fetchRow("SHOW VARIABLES LIKE 'innodb_flush_log_at_trx_commit'");
        } catch (\Throwable $e) {
            return $this->skip($item, detail: $this->t($item, 'read-error', ['error' => $e->getMessage()],
                'Could not query innodb_flush_log_at_trx_commit: ' . $e->getMessage()));
        }

        $value = (string) ($row['Value'] ?? '');
        if ($value === '') {
            return $this->skip($item, detail: $this->t($item, 'no-data', [], 'innodb_flush_log_at_trx_commit could not be read.'));
        }

        $vars = ['current' => $value];

        if ($value === '1') {
            return $this->fail($item,
                detail: $this->t($item, 'pass-acid', $vars, 'innodb_flush_log_at_trx_commit = 1 (ACID strict).'),
                currentValue: $value,
                expectedValue: '2'
            );
        }
        if ($value === '2') {
            return $this->pass($item,
                detail: $this->t($item, 'pass-throughput', $vars,
                    'innodb_flush_log_at_trx_commit = 2 (tracker-throughput optimised).'),
                currentValue: $value,
                expectedValue: '2'
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'unusual', $vars, "innodb_flush_log_at_trx_commit = {$value} (unusual value)."),
            currentValue: $value,
            expectedValue: '2'
        );
    }
}
