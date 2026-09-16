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

class MysqlInnodbEngineCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Db::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Db is not available in this context.'));
        }

        try {
            $rows = \Piwik\Db::fetchAll(
                "SELECT table_name, engine
                   FROM information_schema.tables
                  WHERE table_schema = DATABASE()
                    AND engine IS NOT NULL
                    AND engine <> 'InnoDB'"
            );
        } catch (\Throwable $e) {
            return $this->skip($item, detail: $this->t($item, 'read-error', ['error' => $e->getMessage()],
                'Could not inspect table engines: ' . $e->getMessage()));
        }

        if (empty($rows)) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', [], 'All Matomo tables are using the InnoDB engine.'),
                currentValue: '0 non-InnoDB tables',
                expectedValue: 'only InnoDB'
            );
        }

        $count  = count($rows);
        $sample = array_slice(array_map(static fn($r) => $r['table_name'] ?? $r['TABLE_NAME'] ?? '', $rows), 0, 5);

        return $this->fail($item,
            detail: $this->t($item, 'mixed', ['count' => $count, 'sample' => implode(', ', $sample)],
                "{$count} non-InnoDB tables detected: " . implode(', ', $sample) . ($count > 5 ? '…' : '')),
            currentValue: (string) $count,
            expectedValue: '0'
        );
    }
}
