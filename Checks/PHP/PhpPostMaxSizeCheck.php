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

class PhpPostMaxSizeCheck extends AbstractCheck
{
    private const MIN_BYTES = 32 * 1024 * 1024;

    public function execute(ChecklistItem $item): CheckResult
    {
        $raw   = (string) ini_get('post_max_size');
        $bytes = $this->normalizeSize($raw);
        $vars  = ['current' => $raw];

        if ($bytes === null || $bytes >= self::MIN_BYTES) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars, "post_max_size is set to {$raw}."),
                currentValue: $raw,
                expectedValue: '>= 32M'
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'too-low', $vars, "post_max_size is set to {$raw}, below the recommended 32 MB minimum."),
            currentValue: $raw,
            expectedValue: '>= 32M'
        );
    }
}
