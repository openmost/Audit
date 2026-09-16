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

class EnableAutoUpdateCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $general = $this->readConfigSection('General');
        if ($general === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }
        $autoUpdate  = (string) ($general['enable_auto_update'] ?? '1');
        $multiServer = (string) ($general['multi_server_environment'] ?? '0');
        $vars        = ['current' => $autoUpdate];

        if ($multiServer === '1') {
            if ($autoUpdate === '0') {
                return $this->pass($item,
                    detail: $this->t($item, 'pass-multi', $vars,
                        'multi_server_environment = 1 and enable_auto_update = 0: correct.'),
                    currentValue: '0',
                    expectedValue: '0 (multi-server)'
                );
            }
            return $this->fail($item,
                detail: $this->t($item, 'multi-bad', $vars,
                    'multi_server_environment is enabled but enable_auto_update is still set to 1. Disable it to avoid partial upgrades on the farm.'),
                currentValue: $autoUpdate,
                expectedValue: '0 (multi-server)'
            );
        }

        if ($autoUpdate === '1') {
            return $this->pass($item,
                detail: $this->t($item, 'pass-single', $vars,
                    'enable_auto_update = 1 (recommended for single-server).'),
                currentValue: '1',
                expectedValue: '1 (single-server)'
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'single-disabled', $vars,
                'enable_auto_update is disabled on a single-server instance. Security patches will require manual upgrades.'),
            currentValue: $autoUpdate,
            expectedValue: '1 (single-server)'
        );
    }
}
