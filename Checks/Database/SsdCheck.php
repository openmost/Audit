<?php

namespace Piwik\Plugins\Audit\Checks\Database;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class SsdCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Db::class)) {
            return $this->skip($item, detail: $this->t($item, 'db-unavailable', [], 'Piwik\\Db is not available.'));
        }
        try {
            $row = \Piwik\Db::fetchRow("SHOW VARIABLES LIKE 'innodb_io_capacity'");
        } catch (\Throwable $e) {
            return $this->skip($item, detail: $this->t($item, 'query-failed', ['error' => $e->getMessage()], 'Query failed: {error}'));
        }
        $val = (int) ($row['Value'] ?? 0);
        if ($val >= 2000) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', ['value' => $val], 'innodb_io_capacity = {value}, sized for SSD storage.'),
                currentValue: (string) $val,
                expectedValue: '>= 2000 (SSD)'
            );
        }
        if ($val < 200) {
            return $this->warn($item,
                detail: $this->t($item, 'spinning-disk', ['value' => $val], 'innodb_io_capacity = {value}, sized for spinning disk. Raise to 2000+ if the data dir lives on SSD.'),
                currentValue: (string) $val,
                expectedValue: '>= 2000 (SSD)'
            );
        }
        return $this->skip($item,
            detail: $this->t($item, 'verify-storage', ['value' => $val], 'innodb_io_capacity = {value}, verify storage type with `cat /sys/block/*/queue/rotational`.'),
            currentValue: (string) $val
        );
    }
}
