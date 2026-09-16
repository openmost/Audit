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

class TrackerStatusCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $tracker = $this->readConfigSection('Tracker');
        if ($tracker === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }

        // `[Tracker] record_statistics = 0` flat-out disables the tracker
        // endpoint. We only ever see this in staging environments left
        // behind after a migration — critical to catch.
        $record = (string) ($tracker['record_statistics'] ?? '1');

        if ($record === '1') {
            return $this->pass($item,
                detail: $this->t($item, 'pass', [], 'record_statistics = 1: the tracker is live.'),
                currentValue: '1',
                expectedValue: '1'
            );
        }

        return $this->fail($item,
            detail: $this->t($item, 'disabled', [],
                'record_statistics is disabled: Matomo ignores every incoming hit.'),
            currentValue: $record,
            expectedValue: '1'
        );
    }
}
