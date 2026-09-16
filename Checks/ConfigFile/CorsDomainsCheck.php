<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks\ConfigFile;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class CorsDomainsCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $general = $this->readConfigSection('General');
        if ($general === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }
        $cors = $general['cors_domains'] ?? null;

        if ($cors === null || $cors === '' || (is_array($cors) && count($cors) === 0)) {
            return $this->pass($item,
                detail: $this->t($item, 'empty', [], 'cors_domains is empty (default, safe).'),
                currentValue: '(empty)',
                expectedValue: 'empty or explicit list'
            );
        }

        $list = is_array($cors) ? $cors : [$cors];
        foreach ($list as $domain) {
            if (trim((string) $domain) === '*') {
                return $this->fail($item,
                    detail: $this->t($item, 'wildcard', ['current' => implode(', ', array_map('strval', $list))],
                        'cors_domains contains a wildcard (*), exposing the Matomo API to any origin.'),
                    currentValue: implode(', ', array_map('strval', $list)),
                    expectedValue: 'no wildcard'
                );
            }
        }

        return $this->pass($item,
            detail: $this->t($item, 'pass', ['current' => implode(', ', array_map('strval', $list))],
                'cors_domains is explicitly restricted.'),
            currentValue: implode(', ', array_map('strval', $list)),
            expectedValue: 'no wildcard'
        );
    }
}
