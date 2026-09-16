<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks\General;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class TimezoneConsistencyCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $phpTz   = date_default_timezone_get();
        $mysqlTz = null;
        if (class_exists(\Piwik\Db::class)) {
            try {
                $row = \Piwik\Db::fetchRow("SELECT @@global.time_zone AS global_tz, @@session.time_zone AS session_tz");
                $mysqlTz = $row['session_tz'] ?? $row['SESSION_TZ'] ?? $row['global_tz'] ?? null;
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $vars = ['php' => $phpTz, 'mysql' => (string) $mysqlTz];

        if ($mysqlTz === null) {
            return $this->skip($item,
                detail: $this->t($item, 'no-mysql', $vars, 'Could not read the MySQL session time zone.'),
                currentValue: $phpTz
            );
        }

        if (strtoupper((string) $mysqlTz) === 'SYSTEM') {
            return $this->pass($item,
                detail: $this->t($item, 'system', $vars,
                    "PHP timezone = {$phpTz}, MySQL timezone = {$mysqlTz} (MySQL inherits from the system)."),
                currentValue: "{$phpTz} / {$mysqlTz}",
                expectedValue: 'consistent'
            );
        }

        try {
            $php    = new \DateTimeZone($phpTz);
            $mysql  = new \DateTimeZone($mysqlTz);
            $now    = new \DateTimeImmutable('now');
            $phpOff = $php->getOffset($now);
            $myOff  = $mysql->getOffset($now);

            if ($phpOff === $myOff) {
                return $this->pass($item,
                    detail: $this->t($item, 'pass', $vars,
                        "PHP timezone = {$phpTz}, MySQL timezone = {$mysqlTz} (offsets match)."),
                    currentValue: "{$phpTz} / {$mysqlTz}",
                    expectedValue: 'consistent'
                );
            }

            return $this->warn($item,
                detail: $this->t($item, 'mismatch', $vars,
                    "PHP timezone = {$phpTz}, MySQL timezone = {$mysqlTz}: PHP and MySQL report different UTC offsets."),
                currentValue: "{$phpTz} / {$mysqlTz}",
                expectedValue: 'consistent'
            );
        } catch (\Throwable $e) {
            return $this->warn($item,
                detail: $this->t($item, 'unknown', $vars,
                    "PHP timezone = {$phpTz}, MySQL timezone = {$mysqlTz} (could not normalise MySQL timezone)."),
                currentValue: "{$phpTz} / {$mysqlTz}",
                expectedValue: 'consistent'
            );
        }
    }
}
