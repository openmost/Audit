<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks\Infrastructure;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class WebEngineCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $software = (string) ($_SERVER['SERVER_SOFTWARE'] ?? '');
        $vars     = ['current' => $software];

        if ($software === '' || PHP_SAPI === 'cli') {
            return $this->skip($item,
                detail: $this->t($item, 'cli', $vars, 'Server software not visible from this execution context.'),
                currentValue: $software ?: PHP_SAPI
            );
        }

        if (stripos($software, 'nginx') !== false) {
            return $this->pass($item,
                detail: $this->t($item, 'nginx', $vars, "Web engine is NGINX ({$software})."),
                currentValue: $software,
                expectedValue: 'Apache or NGINX'
            );
        }
        if (stripos($software, 'apache') !== false) {
            return $this->pass($item,
                detail: $this->t($item, 'apache', $vars, "Web engine is Apache ({$software}). NGINX is recommended but Apache is fully supported."),
                currentValue: $software,
                expectedValue: 'Apache or NGINX'
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'unknown', $vars, "Unknown web engine: {$software}."),
            currentValue: $software,
            expectedValue: 'Apache or NGINX'
        );
    }
}
