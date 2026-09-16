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

class ForceSslCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $general = $this->readConfigSection('General');
        if ($general === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }
        $forceSsl = (string) ($general['force_ssl'] ?? '0');

        if ($forceSsl === '1') {
            return $this->pass($item,
                detail: $this->t($item, 'pass', [], 'force_ssl = 1: HTTPS is enforced.'),
                currentValue: '1',
                expectedValue: '1'
            );
        }

        return $this->fail($item,
            detail: $this->t($item, 'disabled', ['current' => $forceSsl],
                'force_ssl is not enabled; HTTPS is not enforced.'),
            currentValue: $forceSsl,
            expectedValue: '1'
        );
    }
}
