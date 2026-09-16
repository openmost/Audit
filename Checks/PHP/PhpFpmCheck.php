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

class PhpFpmCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $sapi = (string) PHP_SAPI;
        $vars = ['current' => $sapi];

        if (stripos($sapi, 'fpm') !== false) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars, "PHP SAPI is {$sapi}."),
                currentValue: $sapi,
                expectedValue: 'fpm'
            );
        }

        if ($sapi === 'cli') {
            return $this->skip($item,
                detail: $this->t($item, 'cli', $vars, 'Running under CLI SAPI: web SAPI is not visible from here.'),
                currentValue: $sapi
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'not-fpm', $vars, "PHP SAPI is {$sapi}; PHP-FPM is recommended for Matomo."),
            currentValue: $sapi,
            expectedValue: 'fpm'
        );
    }
}
