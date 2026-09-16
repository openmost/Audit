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

class PhpMemoryLimitCheck extends AbstractCheck
{
    private const MIN_BYTES = 2 * 1024 * 1024 * 1024;

    public function execute(ChecklistItem $item): CheckResult
    {
        $raw   = (string) ini_get('memory_limit');
        $bytes = $this->normalizeSize($raw);
        $vars  = ['current' => $raw];

        if ($bytes === null) {
            return $this->pass($item,
                detail: $this->t($item, 'unlimited', $vars, 'memory_limit is unlimited.'),
                currentValue: $raw,
                expectedValue: '>= 2G'
            );
        }

        if ($bytes >= self::MIN_BYTES) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars, "memory_limit is set to {$raw} (>= 2 GB)."),
                currentValue: $raw,
                expectedValue: '>= 2G'
            );
        }

        return $this->fail($item,
            detail: $this->t($item, 'too-low', $vars, "memory_limit is set to {$raw}, below the recommended 2 GB minimum."),
            currentValue: $raw,
            expectedValue: '>= 2G'
        );
    }
}
