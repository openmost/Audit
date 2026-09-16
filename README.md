# Openmost Audit: Matomo plugin

## Description

**Openmost Audit** is a free, read-only Matomo On-Premise plugin that
inspects the instance it is installed on and produces a structured
configuration audit report: server, PHP, database, `config.ini.php`
and Matomo general settings, each finding with its severity, the
observed value, the expected value and a recommendation.

It is designed for consultants, ops teams and super users who need a
quick, repeatable way to verify that a Matomo installation is properly
configured, without touching a single setting.

### Features

- **53 automated checks** across 6 categories: Infrastructure & server,
  PHP, Database, `config.ini.php` settings, Matomo general settings,
  Backups & monitoring.
- **Strictly read-only**: never writes to `config.ini.php`, to the
  database or to any file, never stores the results. Every report is
  computed fresh.
- **No outbound HTTP calls** and no telemetry.
- **Admin report** with filters by category, severity, status and a
  free-text search.
- **Markdown export** of the report, ready to paste into a consulting
  deliverable, with an ASCII-only variant for diff-friendly pipelines.
- **Console command** `audit:run` with a table or Markdown output.
- **7 languages**: English, French, German, Chinese (Simplified),
  Italian, Spanish and Swedish.
- **96 more checks ([premium](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit))**:
  Privacy / GDPR, Users & permissions, Web sites, Data quality, Matomo
  Tag Manager, Plugins, Public file exposure, High traffic /
  performance, Multi-server / HA, plus TLS certificate, CDN/WAF, HTTP/2
  and HTTPS redirect checks through read-only HTTP probes. They are
  listed in the report with a "Premium" badge but not run by this
  plugin.

### Requirements

- Matomo 6 (`>=6.0.0-b1,<7.0.0-b1`)
- PHP 8.1 or higher
- MySQL 8.0+ or MariaDB 10.6+

### Installation

Install the plugin from the Matomo Marketplace (Administration >
Marketplace), or copy this directory into `plugins/Audit/` of your
Matomo installation and activate it:

```bash
php console plugin:activate Audit
```

Then open **Administration > Diagnostic > Audit** as a super user.

[Purchase Openmost Audit Premium version](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit)

## Using the plugin

### From the admin UI

- Open **Administration > Diagnostic > Audit** (`?module=Audit`). The
  report is computed on every page load.
- Use the filters to narrow the findings by category, severity, status
  (including "Premium") or free text.
- Click **Export Markdown** to download the report as a `.md` file.
  Append `&plain=1` to the export URL to replace the emoji status badges
  with `[PASS]`, `[FAIL]`, `[WARN]`, `[SKIP]` and `[PREMIUM]` markers.

### From the command line

```bash
# Table output
php console audit:run

# Markdown output
php console audit:run --format=markdown > audit.md

# Only some checks
php console audit:run --only=srv-php-version,cfg-force-ssl

# Debugging helpers
php console audit:debug-metrics
php console audit:debug-translations --locale=fr
```

The table output lists the checks that ran; its summary line also
counts the premium checks that were not run.

## Checks run by the free plugin

| Category | Checks |
|----------|--------|
| Infrastructure & server (14) | Matomo version, web engine, archiving cron, GeoIP database and its freshness, SMTP, write permissions on `tmp/` and `js/`, file integrity, codebase on NFS, tracker cache TTL, tracker status, tracker hostname obfuscation, private directories not reachable, reverse-proxy client headers |
| PHP (8) | Version, PHP-FPM, `memory_limit`, `max_execution_time`, `post_max_size`, OPcache, required extensions, `shell_exec` / `proc_open` |
| Database (14) | Database size, tracking volume, MySQL / MariaDB version, InnoDB engine, `utf8mb4` charset, `max_allowed_packet`, `wait_timeout`, `innodb_flush_log_at_trx_commit`, InnoDB buffer pool, Transitions indexes, database not exposed, slow query log, SSD tuning, MySQL user privileges |
| `config.ini.php` settings (12) | `force_ssl`, `trusted_hosts`, `cors_domains`, login brute-force protection, password complexity, auto-update, multi-server mode, unique visitors for ranges and years, custom reports max dimensions, MariaDB schema, admin IP allowlist |
| Matomo general settings (4) | Custom logo, TrackingSpamPrevention filters, CORS domains in sync with the UI, timezone consistency between PHP, MySQL and Matomo |
| Backups & monitoring (1) | Application logs written to a persistent file |

## Premium checks

The following checks are listed in the report with a "Premium" badge
and run only in the
[premium version](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit):

| Category | Checks |
|----------|--------|
| Infrastructure & server (4) | TLS certificate validity, CDN/WAF in front of Matomo, HTTP/2, HTTPS redirect (read-only HTTP probes) |
| Public file exposure (8) | `matomo.php` and `matomo.js` reachable, opt-out endpoints, favicon, heatmap configuration endpoint, tracker integrity hash (read-only HTTP probes) |
| Plugins (11) | Plugin inventory, invalid, deprecated and outdated plugins, recommended official plugins, TrackingSpamPrevention, QueuedTracking, FormAnalytics |
| Privacy / GDPR (9) | IP and geolocation anonymization, raw and aggregated data retention, PII removal, third-party cookies, Do Not Track, Live reports, Heatmaps and Session Recording |
| Users & permissions (10) | Super user count, 2FA, anonymous access, dormant and shared accounts, API token scope, Tag Manager roles, ActivityLog, read-only MySQL user |
| Web sites (14) | URLs, excluded IPs and query parameters, URL fragments, e-commerce, site search, timezones, currencies, duplicates, heatmap breakpoints, IP geolocation, cross-domain tracking |
| Data quality (16) | Goals, event naming, Custom Dimensions, segments, custom reports, funnels, alerts, annotations, Search Console, tracking failures |
| Matomo Tag Manager (8) | Containers, environments, Matomo tag, naming conventions, per-environment site ID |
| High traffic / performance (12) | Browser archiving, archive freshness, PHP-FPM workers, MySQL connections, slow query log, QueuedTracking with Redis, CDN for tracker assets, segments and custom reports counts, multi-server topology |
| Multi-server / HA (4) | Shared database, read replica, load balancer, dedicated archiver |

The Markdown export ends with the list of the premium checks (title,
id and severity), grouped by category, without any result.

## Using it with the premium version

When the premium plugin (`AuditPremium`) is active, it runs every check
of this plugin too, so this plugin steps aside: its menu entry is
hidden, its page redirects to the premium report and its console
commands are left to the premium plugin. You can then deactivate the
free plugin.

## Security

- Read-only: no write to the database, to `config.ini.php` or to any
  file.
- No outbound HTTP request, no telemetry.
- Every controller action requires super user access.
- The Markdown export only contains the values the checks report,
  never the content of `config.ini.php`, credentials or salts.

## Development

The source code is on GitHub: <https://github.com/openmost/Audit>.

```bash
cd /path/to/matomo

# Unit tests (no Matomo database needed)
php vendor/bin/phpunit -c plugins/Audit/tests/phpunit.xml.dist

# Front-end build (Vite)
php console vue:build Audit
```

## Support

- GitHub issues: <https://github.com/openmost/Audit/issues>
- Email: [ronan@openmost.com](mailto:ronan@openmost.com)

## License

GPL v3 or later
