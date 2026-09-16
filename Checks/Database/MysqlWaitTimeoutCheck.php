<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks\Database;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class MysqlWaitTimeoutCheck extends AbstractCheck
{
    private const RECOMMENDED_MIN = 35000;

    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Db::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Db is not available in this context.'));
        }

        try {
            $row = \Piwik\Db::fetchRow("SHOW VARIABLES LIKE 'wait_timeout'");
        } catch (\Throwable $e) {
            return $this->skip($item, detail: $this->t($item, 'read-error', ['error' => $e->getMessage()],
                'Could not query wait_timeout: ' . $e->getMessage()));
        }

        $value = (int) ($row['Value'] ?? 0);
        if ($value <= 0) {
            return $this->skip($item, detail: $this->t($item, 'no-data', [], 'wait_timeout could not be read.'));
        }

        $vars = ['current' => $value];

        if ($value >= self::RECOMMENDED_MIN) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars, "wait_timeout is {$value}s."),
                currentValue: (string) $value,
                expectedValue: '>= 35000'
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'too-low', $vars,
                "wait_timeout is {$value}s, below the recommended 35000s for long Matomo workloads."),
            currentValue: (string) $value,
            expectedValue: '>= 35000'
        );
    }
}
