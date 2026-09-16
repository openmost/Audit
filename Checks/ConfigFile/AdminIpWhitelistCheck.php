<?php

namespace Piwik\Plugins\Audit\Checks\ConfigFile;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class AdminIpWhitelistCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $g = $this->readConfigSection('General');
        if ($g === null) {
            return $this->skip($item, detail: $this->t($item, 'config-unavailable', [], 'Piwik\\Config is not available.'));
        }
        $ips = $g['login_allowlist_ip'] ?? [];
        if (!is_array($ips)) $ips = [$ips];
        $ips = array_values(array_filter(array_map('strval', $ips), static fn($v) => trim($v) !== ''));
        if (empty($ips)) {
            return $this->warn($item,
                detail: $this->t($item, 'empty', [],
                    'No IP allowlist for the admin login. Restrict access to office/VPN ranges via [General] login_allowlist_ip[].'),
                currentValue: '(empty)',
                expectedValue: 'office/VPN range'
            );
        }
        return $this->pass($item,
            detail: $this->t($item, 'pass', ['count' => count($ips)], '{count} IP range(s) allowed to log in.'),
            currentValue: implode(', ', $ips),
            expectedValue: 'office/VPN range'
        );
    }
}
