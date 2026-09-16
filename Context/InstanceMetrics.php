<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Context;

/**
 * Lazy aggregator of "how big is this Matomo instance" signals.
 *
 * Each value is computed at most once per request and cached on the
 * instance — checks can call the getters freely without worrying about
 * extra SQL round-trips.
 *
 * The values are deliberately *low-precision*: we only use them to pick
 * recommendation thresholds, never to display exact figures, so EXPLAIN
 * shortcuts and approximate counters from `information_schema.tables`
 * are good enough.
 */
class InstanceMetrics
{
    /**
     * Tables that checks are allowed to introspect through raw SQL
     * (interpolation into the query string). Any other table name is
     * refused outright to avoid turning the metrics helper into a vector
     * for arbitrary-schema injection.
     */
    private const ALLOWED_TABLES = [
        'log_link_visit_action',
        'log_visit',
    ];

    private ?int $dbSizeBytes      = null;
    private ?int $totalActions     = null;
    private ?int $totalVisits      = null;
    private ?int $monthlyActions   = null;
    private ?int $largestTableMb   = null;

    /** @var array<string, mixed>|null */
    private ?array $generalConfig = null;

    /** @var string[]|null */
    private ?array $loadedPlugins = null;

    /**
     * Returns the cumulative size (data + index) of every table in the
     * Matomo schema, in bytes. Returns 0 when the metric cannot be
     * computed (no DB connection, missing privileges, etc.).
     */
    public function getDbSizeBytes(): int
    {
        if ($this->dbSizeBytes !== null) {
            return $this->dbSizeBytes;
        }
        $this->dbSizeBytes = 0;

        if (!class_exists(\Piwik\Db::class)) {
            return 0;
        }

        try {
            $row = \Piwik\Db::fetchRow(
                "SELECT
                    COALESCE(SUM(data_length + index_length), 0) AS bytes,
                    COALESCE(MAX(data_length + index_length), 0) AS biggest
                   FROM information_schema.tables
                  WHERE table_schema = DATABASE()"
            );
            $this->dbSizeBytes    = (int) ($row['bytes']   ?? 0);
            $this->largestTableMb = (int) round((int) ($row['biggest'] ?? 0) / 1024 / 1024);
        } catch (\Throwable $e) {
            // ignore — keep 0
        }

        return $this->dbSizeBytes;
    }

    public function getDbSizeGb(): float
    {
        return round($this->getDbSizeBytes() / 1024 / 1024 / 1024, 2);
    }

    public function getLargestTableMb(): int
    {
        if ($this->largestTableMb === null) {
            // Force the join query to populate both metrics in one call.
            $this->getDbSizeBytes();
        }
        return $this->largestTableMb ?? 0;
    }

    /**
     * Total tracked actions over the entire history. Reads
     * `log_link_visit_action` via the Matomo table prefix.
     */
    public function getTotalActions(): int
    {
        if ($this->totalActions !== null) {
            return $this->totalActions;
        }
        $this->totalActions = $this->approximateRowCount('log_link_visit_action');
        return $this->totalActions;
    }

    public function getTotalVisits(): int
    {
        if ($this->totalVisits !== null) {
            return $this->totalVisits;
        }
        $this->totalVisits = $this->approximateRowCount('log_visit');
        return $this->totalVisits;
    }

    /**
     * Read an approximate row count via information_schema.TABLE_ROWS so
     * we never scan the actual log tables (which can hold billions of
     * rows). The result is "good enough" for picking a sizing tier.
     */
    private function approximateRowCount(string $unprefixedTable): int
    {
        $table = $this->prefix($unprefixedTable);
        if ($table === null) {
            return 0;
        }

        try {
            $row = \Piwik\Db::fetchRow(
                "SELECT TABLE_ROWS AS row_count
                   FROM information_schema.tables
                  WHERE table_schema = DATABASE()
                    AND table_name   = ?",
                [$table]
            );
            if (!is_array($row)) {
                return 0;
            }
            foreach (['row_count', 'ROW_COUNT', 'TABLE_ROWS', 'table_rows'] as $key) {
                if (isset($row[$key])) {
                    return (int) $row[$key];
                }
            }
            return (int) reset($row);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Tracked actions over the last 30 days, used to estimate monthly
     * traffic for high-traffic recommendations. Returns 0 when no data.
     */
    public function getMonthlyActions(): int
    {
        if ($this->monthlyActions !== null) {
            return $this->monthlyActions;
        }
        $this->monthlyActions = 0;

        $table = $this->prefix('log_link_visit_action');
        if ($table === null) {
            return 0;
        }

        try {
            // `$table` is already guarded by `prefix()` (whitelist-only)
            // but we quote it again defensively — the identifier must
            // never leak into the query with untrusted characters.
            $quoted = '`' . str_replace('`', '``', $table) . '`';
            // `AS cnt` instead of `AS rows` — the latter is a reserved
            // word in MySQL 8+ and the column would not be returned with
            // that alias, leaving us with a phantom 0.
            $count = \Piwik\Db::fetchOne(
                "SELECT COUNT(*) FROM {$quoted} WHERE server_time >= (NOW() - INTERVAL 30 DAY)"
            );
            $this->monthlyActions = (int) $count;
        } catch (\Throwable $e) {
            // ignore — leave at 0
        }

        return $this->monthlyActions;
    }

    /**
     * Cached read of the `[General]` section. Checks that need several
     * keys should call this rather than `Piwik\Config::getInstance()->General`
     * directly so the instance pays a single lookup per run.
     *
     * @return array<string, mixed>
     */
    public function getGeneralConfig(): array
    {
        if ($this->generalConfig !== null) {
            return $this->generalConfig;
        }
        $this->generalConfig = [];
        if (!class_exists(\Piwik\Config::class)) {
            return $this->generalConfig;
        }
        try {
            $raw = \Piwik\Config::getInstance()->General ?? [];
            $this->generalConfig = is_array($raw) ? $raw : [];
        } catch (\Throwable $e) {
            // keep the empty array
        }
        return $this->generalConfig;
    }

    /**
     * Cached list of currently loaded/active plugin names. Avoids paying
     * `Piwik\Plugin\Manager::getInstance()->getLoadedPluginsName()` once
     * per check (13+ checks look the list up today).
     *
     * @return string[]
     */
    public function getLoadedPlugins(): array
    {
        if ($this->loadedPlugins !== null) {
            return $this->loadedPlugins;
        }
        $this->loadedPlugins = [];
        if (!class_exists(\Piwik\Plugin\Manager::class)) {
            return $this->loadedPlugins;
        }
        try {
            $this->loadedPlugins = \Piwik\Plugin\Manager::getInstance()->getLoadedPluginsName();
        } catch (\Throwable $e) {
            // keep the empty array
        }
        return $this->loadedPlugins;
    }

    public function isPluginActive(string $name): bool
    {
        return in_array($name, $this->getLoadedPlugins(), true);
    }

    /**
     * "S/M/L/XL" sizing classification used by checks to pick a
     * recommendation tier without exposing absolute thresholds in every
     * single check class.
     */
    public function getInstanceSizing(): string
    {
        $monthly = $this->getMonthlyActions();

        if ($monthly >= 5_000_000) return 'xl';
        if ($monthly >= 1_000_000) return 'l';
        if ($monthly >= 250_000)   return 'm';
        return 's';
    }

    public function isHighTraffic(): bool
    {
        return $this->getMonthlyActions() >= 1_000_000;
    }

    public function isLargeDb(): bool
    {
        return $this->getDbSizeBytes() >= 10 * 1024 * 1024 * 1024; // 10 GB
    }

    /**
     * Snapshot every metric we know about. Useful for the InstanceMetrics
     * info checks and for unit tests / debugging.
     *
     * @return array<string, int|string|float>
     */
    public function snapshot(): array
    {
        return [
            'db_bytes'         => $this->getDbSizeBytes(),
            'db_gb'            => $this->getDbSizeGb(),
            'largest_table_mb' => $this->getLargestTableMb(),
            'total_visits'     => $this->getTotalVisits(),
            'total_actions'    => $this->getTotalActions(),
            'monthly_actions'  => $this->getMonthlyActions(),
            'sizing'           => $this->getInstanceSizing(),
        ];
    }

    private function prefix(string $tableName): ?string
    {
        if (!in_array($tableName, self::ALLOWED_TABLES, true)) {
            return null;
        }
        if (!class_exists(\Piwik\Common::class) || !class_exists(\Piwik\Db::class)) {
            return null;
        }
        try {
            return \Piwik\Common::prefixTable($tableName);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
