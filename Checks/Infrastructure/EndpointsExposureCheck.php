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

class EndpointsExposureCheck extends AbstractCheck
{
    private const GUARDED = [
        'config'  => 'config/.htaccess',
        'tmp'     => 'tmp/.htaccess',
        'core'    => 'core/.htaccess',
        'lang'    => 'lang/.htaccess',
    ];

    public function execute(ChecklistItem $item): CheckResult
    {
        $root = defined('PIWIK_DOCUMENT_ROOT') ? PIWIK_DOCUMENT_ROOT : realpath(__DIR__ . '/../../../../');
        if (!is_string($root) || !is_dir($root)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Could not resolve Matomo document root.'));
        }

        $missing = [];
        foreach (self::GUARDED as $dir => $relative) {
            if (!is_dir($root . DIRECTORY_SEPARATOR . $dir)) {
                continue;
            }
            if (!is_file($root . DIRECTORY_SEPARATOR . $relative)) {
                $missing[] = $relative;
            }
        }

        if (empty($missing)) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', [], 'All protected directories ship their .htaccess guard files.'),
                currentValue: 'guarded',
                expectedValue: '.htaccess present'
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'missing-htaccess', ['dirs' => implode(', ', $missing)],
                'Missing .htaccess guards: ' . implode(', ', $missing) . '. On NGINX, add equivalent deny rules.'),
            currentValue: implode(', ', $missing),
            expectedValue: '.htaccess present (Apache) or nginx deny rules'
        );
    }
}
