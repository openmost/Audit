# Openmost Audit

Free, read-only configuration audit of your Matomo 5 On-Premise instance: 53 checks on the server, PHP, the database and `config.ini.php`, flagging what blocks the upgrade to Matomo 6, with a Markdown export.

## Features

- **53 automated checks** across 6 categories: Infrastructure & server, PHP, Database, `config.ini.php` settings, Matomo general settings, Backups & monitoring.
- **Actionable findings**: each finding has a status (pass, fail, warn, skip), a severity (critical, high, medium, low, info), the observed value, the expected value, a recommendation and, when relevant, a configuration snippet.
- **Strictly read-only**: the plugin never writes to `config.ini.php`, to the database or to any file, and never stores the results. Every report is computed fresh.
- **Admin report** for super users, with filters by category, severity, status and free text.
- **Markdown export**, ready to paste into a consulting deliverable, with an ASCII-only variant for diff-friendly pipelines.
- **Console command** `audit:run`, with table or Markdown output and a `--only` filter.
- **Theme-aware code blocks**: configuration snippets follow the light or dark Matomo theme.
- **13 languages**: English, Arabic, Chinese (Simplified), Chinese (Traditional), Dutch, French, German, Italian, Japanese, Polish, Portuguese, Spanish and Swedish.
- **Premium checks listed**: the 96 checks of [Openmost Audit Premium](https://openmost.com/matomo/extensions/audit?utm_source=matomo_marketplace&utm_medium=referral&utm_campaign=premium_upgrade&utm_content=audit) appear in the report with a "Premium" badge, without being run.

### Checks run by the free plugin

| Category | Checks |
|----------|--------|
| Infrastructure & server (14) | Matomo version, web engine, archiving cron, GeoIP database and its freshness, SMTP, write permissions on `tmp/` and `js/`, file integrity, codebase on NFS, tracker cache TTL, tracker status, tracker hostname obfuscation, private directories not reachable, reverse-proxy client headers |
| PHP (8) | Version, PHP-FPM, `memory_limit`, `max_execution_time`, `post_max_size`, OPcache, required extensions, `shell_exec` / `proc_open` |
| Database (14) | Database size, tracking volume, MySQL / MariaDB version, InnoDB engine, `utf8mb4` charset, `max_allowed_packet`, `wait_timeout`, `innodb_flush_log_at_trx_commit`, InnoDB buffer pool, Transitions indexes, database not exposed, slow query log, SSD tuning, MySQL user privileges |
| `config.ini.php` settings (12) | `force_ssl`, `trusted_hosts`, `cors_domains`, login brute-force protection, password complexity, auto-update, multi-server mode, unique visitors for ranges and years, custom reports max dimensions, MariaDB schema, admin IP allowlist |
| Matomo general settings (4) | Custom logo, TrackingSpamPrevention filters, CORS domains in sync with the UI, timezone consistency between PHP, MySQL and Matomo |
| Backups & monitoring (1) | Application logs written to a persistent file |

### Premium checks

The following checks are listed with a "Premium" badge and only run in [Openmost Audit Premium](https://openmost.com/matomo/extensions/audit?utm_source=matomo_marketplace&utm_medium=referral&utm_campaign=premium_upgrade&utm_content=audit):

| Category | Checks |
|----------|--------|
| Infrastructure & server (4) | TLS certificate validity, CDN/WAF in front of Matomo, HTTP/2, HTTPS redirect (read-only HTTP probes) |
| Public file exposure (8) | `matomo.php` and `matomo.js` reachable, opt-out endpoints, favicon, heatmap configuration endpoint, tracker integrity hash (read-only HTTP probes) |
| Plugins (11) | Plugin inventory, invalid, deprecated and outdated plugins, recommended official plugins, TrackingSpamPrevention, QueuedTracking, FormAnalytics |
| Privacy / GDPR (9) | IP and geolocation anonymization, raw and aggregated data retention, PII removal, third-party cookies, Do Not Track, Live reports, Heatmaps and Session Recording |
| Users & permissions (10) | Super user count, 2FA, anonymous access, dormant and shared accounts, API token scope, Tag Manager roles, ActivityLog, read-only MySQL user |
| Web sites (14) | URLs, excluded IPs and query parameters, URL fragments, e-commerce, site search, timezones, currencies, duplicates, heatmap breakpoints, IP geolocation, cross-domain tracking |
| Data quality (16) | Goals, event naming, Custom Dimensions, segments, custom reports, funnels, alerts, annotations, Search Console, tracking failures |
| Matomo Tag Manager (8) | Containers, environments, Matomo tag, naming conventions, per-environment site ID, tracker URL pointing at this Matomo |
| High traffic / performance (12) | Browser archiving, archive freshness, PHP-FPM workers, MySQL connections, slow query log, QueuedTracking with Redis, CDN for tracker assets, segments and custom reports counts, multi-server topology |
| Multi-server / HA (4) | Shared database, read replica, load balancer, dedicated archiver |

The Markdown export ends with the list of the premium checks (title, id and severity), grouped by category, without any result.

## Requirements

- Matomo 5.10.0 or higher (`>=5.10.0,<6.0.0-b1`)
- PHP 8.1 or higher
- A MySQL or MariaDB version supported by Matomo 5

The PHP and database version checks apply the Matomo 6 requirements (PHP 8.1+, MySQL 8.0+ / MariaDB 10.6+) but report them as warnings rather than failures: those versions still run Matomo 5, they only block the upgrade. For Matomo 6, use the 6.x releases of this plugin.

## Installation / Configuration

1. Install the plugin from the Matomo Marketplace (**Administration > Marketplace**), or copy it into `plugins/Audit/` and run `php console plugin:activate Audit`.
2. Open **Administration > Diagnostic > Audit** as a super user. The report is computed on every page load, there is nothing to configure.
3. Click **Export Markdown** to download the report as a `.md` file. Append `&plain=1` to the export URL to replace the emoji status badges with `[PASS]`, `[FAIL]`, `[WARN]`, `[SKIP]` and `[PREMIUM]`.

From the command line:

```bash
php console audit:run
php console audit:run --format=markdown > audit.md
php console audit:run --only=srv-php-version,cfg-force-ssl
```

`audit:debug-metrics` and `audit:debug-translations` are troubleshooting helpers.

### With Audit Premium

AuditPremium runs every check of this plugin too. When it is active, this plugin steps aside: its menu entry is hidden, its page redirects to the premium report and the `audit:*` console commands are the premium ones. You can then deactivate the free plugin.

## Privacy and data

- Read-only: the plugin reads `config.ini.php`, runs read queries (`SELECT`, `SHOW`) and inspects the PHP runtime. It writes nothing and stores nothing.
- No outbound HTTP request and no telemetry.
- Every page and export requires super user access.
- The Markdown export only contains the values the checks report, never the content of `config.ini.php`, credentials or salts.

## Need help with Matomo?

Openmost is an official Matomo Implementation Partner. For an expert review on top of the automated checks, we run [independent Matomo audits](https://openmost.com/matomo/services/audit?utm_source=matomo_marketplace&utm_medium=referral&utm_campaign=services&utm_content=audit) covering tracking, privacy and configuration, delivered as a written report with every fix ranked by impact.

## Support

- Homepage: <https://openmost.com/matomo/extensions/audit>
- Email: [ronan@openmost.com](mailto:ronan@openmost.com)
- Source code and issues: <https://github.com/openmost/Audit>

## Screenshots

See the `screenshots/` folder: the audit summary with the free and premium checks, an expanded finding with its recommendation and configuration snippet, and the premium checks listed in the free edition.

## License

GPL v3 or later
