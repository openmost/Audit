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

class PasswordComplexityCheck extends AbstractCheck
{
    private const RECOMMENDED_MIN = 12;

    public function execute(ChecklistItem $item): CheckResult
    {
        $general = $this->readConfigSection('General');
        if ($general === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }
        $min = (int) ($general['minimum_password_length'] ?? 0);
        $vars    = ['current' => $min];

        if ($min >= self::RECOMMENDED_MIN) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars, "minimum_password_length is {$min}."),
                currentValue: (string) $min,
                expectedValue: '>= 12'
            );
        }

        if ($min > 0) {
            return $this->warn($item,
                detail: $this->t($item, 'too-low', $vars,
                    "minimum_password_length is {$min}, below the recommended 12."),
                currentValue: (string) $min,
                expectedValue: '>= 12'
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'default', $vars,
                'minimum_password_length is not set; Matomo falls back to its internal default.'),
            currentValue: '(default)',
            expectedValue: '>= 12'
        );
    }
}
