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

class TrustedHostsCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $general = $this->readConfigSection('General');
        if ($general === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }
        $hosts = $general['trusted_hosts'] ?? [];
        $hosts   = is_array($hosts) ? $hosts : [$hosts];
        $hosts   = array_values(array_filter(array_map('strval', $hosts), static fn($h) => trim($h) !== ''));

        if (empty($hosts)) {
            return $this->fail($item,
                detail: $this->t($item, 'empty', [], 'trusted_hosts[] is empty: Matomo is open to Host header poisoning.'),
                currentValue: '(empty)',
                expectedValue: 'at least one explicit host'
            );
        }

        $count = count($hosts);
        return $this->pass($item,
            detail: $this->t($item, 'pass', ['count' => $count],
                "trusted_hosts[] contains {$count} entr" . ($count === 1 ? 'y' : 'ies') . '.'),
            currentValue: (string) $count,
            expectedValue: '>= 1'
        );
    }
}
