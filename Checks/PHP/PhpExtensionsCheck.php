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

class PhpExtensionsCheck extends AbstractCheck
{
    private const REQUIRED = [
        'pdo', 'pdo_mysql', 'gd', 'curl', 'mbstring', 'xml', 'zlib', 'json', 'openssl',
    ];

    public function execute(ChecklistItem $item): CheckResult
    {
        $missing = [];
        foreach (self::REQUIRED as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        if (empty($missing)) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', [], 'All required PHP extensions are loaded.'),
                currentValue: implode(', ', self::REQUIRED),
                expectedValue: implode(', ', self::REQUIRED)
            );
        }

        return $this->fail($item,
            detail: $this->t($item, 'missing', ['missing' => implode(', ', $missing)],
                'Missing required PHP extensions: ' . implode(', ', $missing)
            ),
            currentValue: implode(', ', $missing),
            expectedValue: implode(', ', self::REQUIRED)
        );
    }
}
