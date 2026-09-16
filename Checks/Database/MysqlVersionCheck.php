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

class MysqlVersionCheck extends AbstractCheck
{
    // Matomo 6 minimums
    public const MIN_MYSQL   = '8.0.0';
    public const MIN_MARIADB = '10.6.0';

    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Db::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Db is not available in this context.'));
        }

        try {
            $version = (string) \Piwik\Db::fetchOne('SELECT VERSION()');
        } catch (\Throwable $e) {
            return $this->skip($item, detail: $this->t($item, 'read-error', ['error' => $e->getMessage()],
                'Could not read MySQL version: ' . $e->getMessage()));
        }

        $isMariaDb  = stripos($version, 'maria') !== false;
        $normalized = self::normalizeVersion($version);
        $vars       = ['current' => $version];

        if ($isMariaDb) {
            if (version_compare($normalized, self::MIN_MARIADB, '>=')) {
                return $this->pass($item,
                    detail: $this->t($item, 'mariadb', $vars, "Database is MariaDB {$version}, which meets the Matomo 6 minimum (10.6)."),
                    currentValue: $version,
                    expectedValue: 'MariaDB >= 10.6'
                );
            }

            return $this->fail($item,
                detail: $this->t($item, 'mariadb-too-old', $vars, "MariaDB {$version} is below the Matomo 6 minimum (10.6)."),
                currentValue: $version,
                expectedValue: 'MariaDB >= 10.6'
            );
        }

        if (version_compare($normalized, self::MIN_MYSQL, '>=')) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars, "MySQL {$version} meets the Matomo 6 minimum (8.0)."),
                currentValue: $version,
                expectedValue: 'MySQL >= 8.0'
            );
        }

        return $this->fail($item,
            detail: $this->t($item, 'too-old', $vars, "MySQL {$version} is below the Matomo 6 minimum (8.0)."),
            currentValue: $version,
            expectedValue: 'MySQL >= 8.0'
        );
    }

    /**
     * `8.4.7` → `8.4.7`, `10.6.12-MariaDB-log` → `10.6.12`, `5.5.5-10.6.12-MariaDB` → `10.6.12`
     * (old MariaDB releases prefix the version with the MySQL protocol version).
     */
    public static function normalizeVersion(string $version): string
    {
        $version = preg_replace('/^5\.5\.5-/', '', $version) ?? $version;

        return preg_replace('/[^0-9.].*$/', '', $version) ?: $version;
    }
}
