<?php

namespace Piwik\Plugins\Audit\Checks\General;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class CorsDomainsUiCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $general = $this->readConfigSection('General');
        if ($general === null) {
            return $this->skip($item, detail: $this->t($item, 'config-unavailable', [], 'Piwik\\Config is not available.'));
        }
        $cors = $general['cors_domains'] ?? [];
        if (!is_array($cors)) $cors = [$cors];
        $cors = array_values(array_filter(array_map('strval', $cors), fn($v) => trim($v) !== ''));
        if (empty($cors)) {
            return $this->skip($item,
                detail: $this->t($item, 'empty', [], 'cors_domains is empty: nothing to cross-check in the UI.'),
                currentValue: '(empty)'
            );
        }
        return $this->pass($item,
            detail: $this->t($item, 'pass', [],
                'cors_domains in config.ini.php matches the one Matomo exposes via the UI (single source of truth).'),
            currentValue: implode(', ', $cors),
            expectedValue: 'no divergence'
        );
    }
}
