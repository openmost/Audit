<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks\PHP;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class PhpShellExecCheck extends AbstractCheck
{
    private const REQUIRED_FUNCTIONS = ['shell_exec', 'proc_open'];

    public function execute(ChecklistItem $item): CheckResult
    {
        $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));

        $missing = [];
        foreach (self::REQUIRED_FUNCTIONS as $fn) {
            if (!function_exists($fn) || in_array($fn, $disabled, true)) {
                $missing[] = $fn;
            }
        }

        if (empty($missing)) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', [], 'shell_exec and proc_open are available.'),
                currentValue: 'available',
                expectedValue: implode(', ', self::REQUIRED_FUNCTIONS)
            );
        }

        return $this->fail($item,
            detail: $this->t($item, 'disabled', ['missing' => implode(', ', $missing)],
                'Required functions are disabled: ' . implode(', ', $missing)
            ),
            currentValue: 'disabled: ' . implode(', ', $missing),
            expectedValue: implode(', ', self::REQUIRED_FUNCTIONS)
        );
    }
}
