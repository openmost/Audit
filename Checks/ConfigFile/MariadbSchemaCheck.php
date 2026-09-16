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

class MariadbSchemaCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $section = $this->readConfigSection('database');
        if ($section === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }
        $schema = (string) ($section['schema'] ?? 'Mysql');
        $vars   = ['current' => $schema];

        $isMariaDb = false;
        if (class_exists(\Piwik\Db::class)) {
            try {
                $version   = (string) \Piwik\Db::fetchOne('SELECT VERSION()');
                $isMariaDb = stripos($version, 'maria') !== false;
            } catch (\Throwable $e) {
                // ignore — treat as unknown
            }
        }

        if (!$isMariaDb) {
            return $this->pass($item,
                detail: $this->t($item, 'mysql', $vars,
                    "Database is not MariaDB; schema = {$schema} is appropriate."),
                currentValue: $schema,
                expectedValue: 'Mysql (on MySQL)'
            );
        }

        if (strcasecmp($schema, 'Mariadb') === 0) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars,
                    'MariaDB detected and [database] schema = Mariadb.'),
                currentValue: $schema,
                expectedValue: 'Mariadb'
            );
        }

        return $this->fail($item,
            detail: $this->t($item, 'mismatch', $vars,
                "MariaDB detected but [database] schema = {$schema}. Set it to Mariadb for correct schema handling."),
            currentValue: $schema,
            expectedValue: 'Mariadb'
        );
    }
}
