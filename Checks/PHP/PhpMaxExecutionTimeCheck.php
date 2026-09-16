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

class PhpMaxExecutionTimeCheck extends AbstractCheck
{
    private const RECOMMENDED = 3600;

    public function execute(ChecklistItem $item): CheckResult
    {
        $seconds = (int) ini_get('max_execution_time');
        $vars    = ['current' => $seconds];

        if ($seconds === 0) {
            return $this->pass($item,
                detail: $this->t($item, 'unlimited', $vars, 'max_execution_time is unlimited (0).'),
                currentValue: '0',
                expectedValue: '3600 or 0'
            );
        }

        if ($seconds >= self::RECOMMENDED) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars, "max_execution_time is {$seconds}s."),
                currentValue: (string) $seconds,
                expectedValue: '>= 3600 or 0'
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'too-low', $vars, "max_execution_time is {$seconds}s, below the recommended 3600s."),
            currentValue: (string) $seconds,
            expectedValue: '3600 or 0'
        );
    }
}
