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
    // Matomo 6 minimum: still reported on Matomo 5, as a warning, so the
    // instance can raise its PHP version before the upgrade.
    public const MIN_VERSION = '8.1.0';

    // oldest release still receiving security fixes (PHP 8.1 security support ended on 2025-12-31)
    public const RECOMMENDED_VERSION = '8.2.0';

    /**
     * Overridable so the decision can be exercised on other PHP versions
     * than the one running the test suite.
     */
    protected function currentVersion(): string
    {
        return PHP_VERSION;
    }

    public function execute(ChecklistItem $item): CheckResult
    {
        $current = $this->currentVersion();
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
                detail: $this->t($item, 'eol', $vars, "PHP {$current} runs Matomo 5 and 6 but no longer receives security fixes, upgrade to PHP 8.2 or later."),
                currentValue: $current,
                expectedValue: '>= 8.2'
            );
        }

        // Warning rather than failure: this PHP version still runs Matomo 5,
        // it only blocks the upgrade to Matomo 6.
        return $this->warn($item,
            detail: $this->t($item, 'too-old', $vars, "PHP {$current} runs Matomo 5 but is below the Matomo 6 minimum (8.1): upgrade PHP before migrating."),
            currentValue: $current,
            expectedValue: '>= 8.2'
        );
    }
}
