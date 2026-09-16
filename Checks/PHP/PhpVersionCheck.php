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

class PhpVersionCheck extends AbstractCheck
{
    // Matomo 6 minimum
    public const MIN_VERSION = '8.1.0';

    // oldest release still receiving security fixes (PHP 8.1 security support ended on 2025-12-31)
    public const RECOMMENDED_VERSION = '8.2.0';

    public function execute(ChecklistItem $item): CheckResult
    {
        $current = PHP_VERSION;
        $vars    = ['current' => $current];

        if (version_compare($current, self::RECOMMENDED_VERSION, '>=')) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars, "PHP {$current} is supported."),
                currentValue: $current,
                expectedValue: '>= 8.2'
            );
        }

        if (version_compare($current, self::MIN_VERSION, '>=')) {
            return $this->warn($item,
                detail: $this->t($item, 'eol', $vars, "PHP {$current} runs Matomo 6 but no longer receives security fixes, upgrade to PHP 8.2 or later."),
                currentValue: $current,
                expectedValue: '>= 8.2'
            );
        }

        return $this->fail($item,
            detail: $this->t($item, 'too-old', $vars, "PHP {$current} is below the Matomo 6 minimum (8.1)."),
            currentValue: $current,
            expectedValue: '>= 8.2'
        );
    }
}
