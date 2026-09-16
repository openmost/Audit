<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks;

use Piwik\Plugins\Audit\Checklist\ChecklistItem;
use Piwik\Plugins\Audit\Checklist\ChecklistLoader;
use Piwik\Plugins\Audit\Context\InstanceMetrics;
use Piwik\Plugins\Audit\Support\CheckTranslator;

class CheckRunner
{
    private ChecklistLoader $loader;
    private InstanceMetrics $metrics;

    /**
     * Map of check item id prefix → PSR-4 sub-namespace.
     *
     * Order matters: more specific prefixes come first.
     *
     * @var array<string, string>
     */
    private array $prefixNamespaceMap = [
        'srv-matomo-' => 'Infrastructure',
        'srv-php-'    => 'PHP',
        'srv-mysql-'  => 'Database',
        'srv-'        => 'Infrastructure',
        'cfg-'        => 'ConfigFile',
        'gen-'        => 'General',
        'ops-'        => 'Operations',
    ];

    /**
     * Manual id → suffix overrides when the default kebab→pascal mapping
     * is ambiguous or when the id prefix strategy loses a meaningful token.
     *
     * @var array<string, string>
     */
    private array $classSuffixOverrides = [
        'srv-matomo-version'            => 'MatomoVersionCheck',
        'srv-php-version'               => 'PhpVersionCheck',
        'srv-php-memory-limit'          => 'PhpMemoryLimitCheck',
        'srv-php-opcache'               => 'PhpOpcacheCheck',
        'srv-php-post-max-size'         => 'PhpPostMaxSizeCheck',
        'srv-php-fpm'                   => 'PhpFpmCheck',
        'srv-php-max-execution-time'    => 'PhpMaxExecutionTimeCheck',
        'srv-php-extensions'            => 'PhpExtensionsCheck',
        'srv-php-shell-exec'            => 'PhpShellExecCheck',
        'srv-mysql-version'             => 'MysqlVersionCheck',
        'srv-mysql-innodb-buffer-pool'  => 'MysqlInnodbBufferPoolCheck',
        'srv-mysql-indexes-transitions' => 'MysqlTransitionsIndexesCheck',
        'srv-mysql-engine-innodb'       => 'MysqlInnodbEngineCheck',
        'srv-mysql-slow-query-log'      => 'MysqlSlowQueryLogCheck',
        'srv-mysql-user-permissions'    => 'MysqlUserPermissionsCheck',
        'srv-mysql-max-allowed-packet'  => 'MysqlMaxAllowedPacketCheck',
        'srv-mysql-wait-timeout'        => 'MysqlWaitTimeoutCheck',
        'srv-mysql-innodb-flush-log'    => 'MysqlInnodbFlushLogCheck',
        'srv-mysql-charset'             => 'MysqlCharsetCheck',
        'srv-db-size'                   => 'DbSizeCheck',
        'srv-tracking-volume'           => 'TrackingVolumeCheck',
        'srv-cron-archiver'             => 'CronArchiverCheck',
        'srv-tracker-status'            => 'TrackerStatusCheck',
        'srv-tracker-cache-short'       => 'TrackerCacheShortCheck',
        'ops-logs-enabled'              => 'LogsEnabledCheck',
        'srv-reverse-proxy-xff'         => 'ReverseProxyXForwardedForCheck',
        'srv-file-permissions'          => 'FilePermissionsCheck',
        'srv-file-integrity'            => 'FileIntegrityCheck',
        'srv-endpoints-exposure'        => 'EndpointsExposureCheck',
        'srv-geoip-db'                  => 'GeoipDbCheck',
        'srv-smtp-configured'           => 'SmtpConfiguredCheck',
        'cfg-cors-domains'              => 'CorsDomainsCheck',
        'cfg-force-ssl'                 => 'ForceSslCheck',
        'cfg-multi-server-environment'  => 'MultiServerEnvironmentCheck',
        'gen-branding-logo'             => 'BrandingLogoCheck',
        'gen-tracking-spam-options'     => 'TrackingSpamOptionsCheck',
        'gen-timezone-consistency'      => 'TimezoneConsistencyCheck',
        'gen-cors-domains-ui'           => 'CorsDomainsUiCheck',
        'srv-nfs-codebase'              => 'NfsCodebaseCheck',
        'srv-collect-domain-obfuscated' => 'CollectDomainObfuscatedCheck',
        'srv-geoip-freshness'           => 'GeoipFreshnessCheck',
        'cfg-admin-ip-whitelist'        => 'AdminIpWhitelistCheck',
        'srv-mysql-db-not-exposed'      => 'DbNotExposedCheck',
        'srv-mysql-ssd'                 => 'SsdCheck',
    ];

    /**
     * Optional pre-built check instances keyed by item id. Used for
     * dependency injection in tests — if the runner finds a matching
     * instance here it will skip autoloader resolution.
     *
     * @var array<string, CheckInterface>
     */
    private array $overrides = [];

    public function __construct(
        ?ChecklistLoader $loader = null,
        ?InstanceMetrics $metrics = null
    ) {
        $this->loader  = $loader  ?? new ChecklistLoader();
        $this->metrics = $metrics ?? new InstanceMetrics();
    }

    public function getMetrics(): InstanceMetrics
    {
        return $this->metrics;
    }

    public function overrideCheck(string $itemId, CheckInterface $check): void
    {
        $this->overrides[$itemId] = $check;
    }

    /**
     * Run every item from the checklist that has a resolvable PHP check
     * class. Items whose implementation does not yet exist are silently
     * skipped, allowing the checklist to grow ahead of the code.
     *
     * @param string[] $onlyIds Optional whitelist of item ids to run.
     */
    public function run(array $onlyIds = []): AuditReport
    {
        $items   = $this->loader->loadItems();
        $results = [];
        foreach ($items as $item) {
            if (!empty($onlyIds) && !in_array($item->id, $onlyIds, true)) {
                continue;
            }
            if ($item->premium) {
                $results[] = new CheckResult(
                    itemId: $item->id,
                    title: CheckTranslator::title($item),
                    category: $item->category,
                    status: CheckResult::STATUS_PREMIUM,
                    severity: $item->severity
                );
                continue;
            }
            $check = $this->resolveCheck($item);
            if ($check === null) {
                continue;
            }
            try {
                $results[] = $check->execute($item);
            } catch (\Throwable $e) {
                $results[] = new CheckResult(
                    itemId: $item->id,
                    title: $item->title,
                    category: $item->category,
                    status: CheckResult::STATUS_SKIP,
                    severity: $item->severity,
                    detail: 'Check raised an exception: ' . $e->getMessage()
                );
            }
        }

        return new AuditReport(
            results: $results,
            generatedAt: gmdate('Y-m-d\TH:i:s\Z'),
            matomoVersion: class_exists(\Piwik\Version::class) ? \Piwik\Version::VERSION : 'unknown',
            phpVersion: PHP_VERSION,
            pluginVersion: $this->loadPluginVersion()
        );
    }

    public function resolveCheck(ChecklistItem $item): ?CheckInterface
    {
        // Premium items only exist in AuditPremium: never run anything for
        // them, whatever class may be found under Checks/.
        if ($item->premium) {
            return null;
        }
        if (isset($this->overrides[$item->id])) {
            return $this->overrides[$item->id];
        }

        $className = $this->resolveCheckClass($item->id);
        if ($className === null || !class_exists($className)) {
            return null;
        }
        $instance = new $className();
        if (!$instance instanceof CheckInterface) {
            return null;
        }
        if ($instance instanceof MetricsAwareInterface || $instance instanceof AbstractCheck) {
            $instance->setMetrics($this->metrics);
        }
        return $instance;
    }

    public function resolveCheckClass(string $itemId): ?string
    {
        $baseNamespace = 'Piwik\\Plugins\\Audit\\Checks\\';

        foreach ($this->prefixNamespaceMap as $prefix => $subNamespace) {
            if (!str_starts_with($itemId, $prefix)) {
                continue;
            }
            if (isset($this->classSuffixOverrides[$itemId])) {
                return $baseNamespace . $subNamespace . '\\' . $this->classSuffixOverrides[$itemId];
            }
            $remainder = substr($itemId, strlen($prefix));
            $class     = $this->kebabToPascal($remainder) . 'Check';
            return $baseNamespace . $subNamespace . '\\' . $class;
        }
        return null;
    }

    private function kebabToPascal(string $kebab): string
    {
        $parts = explode('-', $kebab);
        return implode('', array_map(static fn($p) => ucfirst($p), $parts));
    }

    private function loadPluginVersion(): string
    {
        $manifest = __DIR__ . '/../plugin.json';
        if (!is_readable($manifest)) {
            return '0.0.0';
        }
        $decoded = json_decode((string) file_get_contents($manifest), true);
        return (string) ($decoded['version'] ?? '0.0.0');
    }
}
