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

class MysqlUserPermissionsCheck extends AbstractCheck
{
    private const REQUIRED = [
        'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'INDEX',
        'DROP', 'ALTER', 'CREATE TEMPORARY TABLES', 'LOCK TABLES',
    ];
    private const OPTIONAL = ['FILE'];

    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Db::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Db is not available in this context.'));
        }

        $grantsText = null;
        try {
            $rows = \Piwik\Db::fetchAll('SHOW GRANTS FOR CURRENT_USER()');
            $buf  = '';
            foreach ($rows as $row) {
                foreach ($row as $value) {
                    $buf .= ' ' . (string) $value;
                }
            }
            $grantsText = $buf;
        } catch (\Throwable $e) {
            // Fall back to information_schema (works when the user can't
            // invoke SHOW GRANTS but still has USAGE on the schema).
            try {
                $rows = \Piwik\Db::fetchAll(
                    'SELECT PRIVILEGE_TYPE FROM information_schema.USER_PRIVILEGES'
                );
                $buf = '';
                foreach ($rows as $row) {
                    foreach ($row as $value) {
                        $buf .= ' ' . (string) $value;
                    }
                }
                $grantsText = $buf;
            } catch (\Throwable $e2) {
                return $this->skip($item, detail: $this->t($item, 'read-error', ['error' => $e->getMessage()],
                    'Could not read grants: ' . $e->getMessage()));
            }
        }

        $grantsUpper = strtoupper($grantsText ?? '');
        $hasAll      = str_contains($grantsUpper, 'ALL PRIVILEGES');

        $missing = [];
        foreach (self::REQUIRED as $priv) {
            if ($hasAll) break;
            if (!$this->grantsContain($grantsUpper, $priv)) {
                $missing[] = $priv;
            }
        }

        $missingOptional = [];
        foreach (self::OPTIONAL as $priv) {
            if (!$hasAll && !$this->grantsContain($grantsUpper, $priv)) {
                $missingOptional[] = $priv;
            }
        }

        if (empty($missing)) {
            $variant = $hasAll ? 'pass-all' : 'pass';
            $fallback = $hasAll
                ? 'Database user holds ALL PRIVILEGES.'
                : 'Database user has all required Matomo privileges.';
            if (!empty($missingOptional)) {
                $fallback .= ' Optional FILE privilege is missing: LOAD DATA INFILE imports will not work.';
                $variant   = $hasAll ? 'pass-all-no-file' : 'pass-no-file';
            }

            return $this->pass($item,
                detail: $this->t($item, $variant, [], $fallback),
                currentValue: $hasAll ? 'ALL PRIVILEGES' : 'granted',
                expectedValue: implode(', ', self::REQUIRED)
            );
        }

        return $this->fail($item,
            detail: $this->t($item, 'missing', ['missing' => implode(', ', $missing)],
                'Missing required privileges: ' . implode(', ', $missing)),
            currentValue: 'missing: ' . implode(', ', $missing),
            expectedValue: implode(', ', self::REQUIRED)
        );
    }

    private function grantsContain(string $grantsUpper, string $priv): bool
    {
        if (str_contains($priv, ' ')) {
            return str_contains($grantsUpper, $priv);
        }
        return (bool) preg_match('/\b' . preg_quote($priv, '/') . '\b/', $grantsUpper);
    }
}
