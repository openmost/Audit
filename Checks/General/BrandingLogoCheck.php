<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks\General;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class BrandingLogoCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Config::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }

        $branding  = \Piwik\Config::getInstance()->branding ?? [];
        $useCustom = (string) ($branding['use_custom_logo'] ?? '0') === '1';

        // Informational only — missing a custom logo is not a bug.
        if ($useCustom) {
            return $this->pass($item,
                detail: $this->t($item, 'custom', [], 'Custom logo is enabled.'),
                currentValue: 'custom',
                expectedValue: 'informational'
            );
        }

        return $this->pass($item,
            detail: $this->t($item, 'default', [], 'No custom logo uploaded. Personalise in Administration → General settings → Brand if needed.'),
            currentValue: 'default',
            expectedValue: 'informational'
        );
    }
}
