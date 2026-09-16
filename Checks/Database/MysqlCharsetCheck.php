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

class MysqlCharsetCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Db::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Db is not available in this context.'));
        }

        try {
            $rows = \Piwik\Db::fetchAll(
                "SELECT table_name, table_collation
                   FROM information_schema.tables
                  WHERE table_schema = DATABASE()
                    AND table_collation IS NOT NULL
                    AND table_collation NOT LIKE 'utf8mb4%'"
            );
        } catch (\Throwable $e) {
            return $this->skip($item, detail: $this->t($item, 'read-error', ['error' => $e->getMessage()],
                'Could not inspect table charsets: ' . $e->getMessage()));
        }

        if (empty($rows)) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', [], 'All Matomo tables use a utf8mb4 collation.'),
                currentValue: 'utf8mb4',
                expectedValue: 'utf8mb4'
            );
        }

        $count  = count($rows);
        $sample = array_slice(array_map(
            static fn($r) => ($r['table_name'] ?? $r['TABLE_NAME'] ?? '') . ' (' . ($r['table_collation'] ?? $r['TABLE_COLLATION'] ?? '') . ')',
            $rows
        ), 0, 5);

        return $this->fail($item,
            detail: $this->t($item, 'mixed', ['count' => $count, 'sample' => implode(', ', $sample)],
                "{$count} tables are not using utf8mb4: " . implode(', ', $sample) . ($count > 5 ? '…' : '')),
            currentValue: (string) $count,
            expectedValue: '0'
        );
    }
}
