<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks\ConfigFile;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class LoginBruteforceCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $general = $this->readConfigSection('General');
        if ($general === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }
        $enableBruteForce = $general['enable_login_brute_force_protection'] ?? null;
        $allowlist        = $general['login_allowlist_ip'] ?? [];

        if ($enableBruteForce !== null && (int) $enableBruteForce === 0) {
            return $this->fail($item,
                detail: $this->t($item, 'disabled', [],
                    'enable_login_brute_force_protection is explicitly disabled in config.ini.php.'),
                currentValue: '0',
                expectedValue: '1 (or unset)'
            );
        }

        $allowlistCount = is_array($allowlist) ? count(array_filter($allowlist)) : 0;
        $variant = $allowlistCount > 0 ? 'pass-allowlist' : 'pass-default';
        $fallback = $allowlistCount > 0
            ? "Brute-force protection active: {$allowlistCount} IP allowlist entr" . ($allowlistCount === 1 ? 'y' : 'ies') . '.'
            : 'Brute-force protection uses the default Matomo configuration.';

        return $this->pass($item,
            detail: $this->t($item, $variant, ['count' => $allowlistCount], $fallback),
            currentValue: $allowlistCount > 0 ? "allowlist: {$allowlistCount}" : 'default',
            expectedValue: 'enabled'
        );
    }
}
