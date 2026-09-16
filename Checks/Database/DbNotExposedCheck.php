<?php

namespace Piwik\Plugins\Audit\Checks\Database;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class DbNotExposedCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Db::class)) {
            return $this->skip($item, detail: $this->t($item, 'db-unavailable', [], 'Piwik\\Db is not available.'));
        }
        try {
            $bind = \Piwik\Db::fetchRow("SHOW VARIABLES LIKE 'bind_address'");
        } catch (\Throwable $e) {
            return $this->skip($item, detail: $this->t($item, 'query-failed', ['error' => $e->getMessage()], 'Query failed: {error}'));
        }
        $value = (string) ($bind['Value'] ?? '');
        $db = $this->readConfigSection('database');
        $host = (string) ($db['host'] ?? '127.0.0.1');
        $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        if (in_array($value, ['127.0.0.1', '::1'], true) || $isLocal) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', ['value' => $value, 'host' => $host], 'MySQL bind_address={value}, Matomo connects via {host}: database not exposed to the network.'),
                currentValue: $value,
                expectedValue: '127.0.0.1 or socket'
            );
        }
        if ($value === '0.0.0.0' || $value === '*') {
            return $this->warn($item,
                detail: $this->t($item, 'listens-all', ['value' => $value], 'MySQL listens on {value}. Confirm a firewall blocks port 3306 from public networks.'),
                currentValue: $value,
                expectedValue: '127.0.0.1 or firewalled'
            );
        }
        return $this->skip($item, detail: $this->t($item, 'verify-firewall', ['value' => $value], 'bind_address={value}: verify firewall rules manually.'));
    }
}
