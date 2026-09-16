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

class MysqlTransitionsIndexesCheck extends AbstractCheck
{
    private const EXPECTED = ['transitions_url', 'transitions_url_ref'];

    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Db::class) || !class_exists(\Piwik\Common::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik DB helpers are not available in this context.'));
        }

        $table = \Piwik\Common::prefixTable('log_link_visit_action');

        try {
            $indexes = \Piwik\Db::fetchAll("SHOW INDEXES FROM `{$table}`");
        } catch (\Throwable $e) {
            return $this->skip($item, detail: $this->t($item, 'read-error', ['error' => $e->getMessage()],
                'Could not inspect log_link_visit_action indexes: ' . $e->getMessage()));
        }

        $found = [];
        foreach ($indexes as $row) {
            $name = $row['Key_name'] ?? null;
            if (is_string($name)) {
                $found[$name] = true;
            }
        }

        $missing = array_values(array_diff(self::EXPECTED, array_keys($found)));
        if (empty($missing)) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', [], 'Transitions indexes are present.'),
                currentValue: implode(', ', self::EXPECTED),
                expectedValue: implode(', ', self::EXPECTED)
            );
        }

        return $this->fail($item,
            detail: $this->t($item, 'missing', ['missing' => implode(', ', $missing)],
                'Missing transitions indexes: ' . implode(', ', $missing)),
            currentValue: empty(array_intersect(self::EXPECTED, array_keys($found)))
                ? 'none'
                : implode(', ', array_intersect(self::EXPECTED, array_keys($found))),
            expectedValue: implode(', ', self::EXPECTED)
        );
    }
}
