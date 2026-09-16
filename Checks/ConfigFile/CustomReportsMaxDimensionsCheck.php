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

class CustomReportsMaxDimensionsCheck extends AbstractCheck
{
    private const EXPECTED = 9;

    public function execute(ChecklistItem $item): CheckResult
    {
        $section = $this->readConfigSection('CustomReports');
        if ($section === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }
        $value = isset($section['custom_reports_max_dimensions'])
            ? (int) $section['custom_reports_max_dimensions']
            : null;

        if ($value === null) {
            return $this->skip($item,
                detail: $this->t($item, 'default', [],
                    '[CustomReports] custom_reports_max_dimensions is not set; Matomo uses the plugin default.'),
                currentValue: '(default)'
            );
        }

        $vars = ['current' => $value];

        if ($value === self::EXPECTED) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars, "custom_reports_max_dimensions is {$value}."),
                currentValue: (string) $value,
                expectedValue: '= 9'
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'mismatch', $vars,
                "custom_reports_max_dimensions is {$value}; the recommended value is exactly 9."),
            currentValue: (string) $value,
            expectedValue: '= 9'
        );
    }
}
