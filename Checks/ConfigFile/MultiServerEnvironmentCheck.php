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

class MultiServerEnvironmentCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $general = $this->readConfigSection('General');
        if ($general === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }
        $value = (string) ($general['multi_server_environment'] ?? '0');
        $vars    = ['current' => $value];

        $variant  = $value === '1' ? 'multi' : 'single';
        $fallback = $value === '1'
            ? 'multi_server_environment = 1 (multi-server mode is active).'
            : 'multi_server_environment = 0 (single-server mode).';

        return $this->pass($item,
            detail: $this->t($item, $variant, $vars, $fallback),
            currentValue: $value,
            expectedValue: '0 or 1 depending on topology'
        );
    }
}
