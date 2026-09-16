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

class UniqueVisitorsYearCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $general = $this->readConfigSection('General');
        if ($general === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }
        $value = (string) ($general['enable_processing_unique_visitors_year'] ?? '1');

        return $this->pass($item,
            detail: $this->t($item, 'pass', ['current' => $value],
                "enable_processing_unique_visitors_year = {$value}."),
            currentValue: $value,
            expectedValue: '0 on very large DBs, 1 otherwise'
        );
    }
}
