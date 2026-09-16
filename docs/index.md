## Description

**Openmost Audit** is a free, read-only plugin that audits the
configuration of your Matomo On-Premise instance and produces an
actionable, structured report, **without touching a single setting**.

It is designed for consultants, ops teams and super users who need a
quick, repeatable way to check that a Matomo installation is properly
configured.

## What the free plugin checks

The free plugin runs **53 automated checks** across 6 categories:

- **Infrastructure & server** (14): Matomo version, web engine,
  archiving cron, GeoIP database and its freshness, SMTP, write
  permissions on `tmp/` and `js/`, file integrity, codebase on NFS,
  tracker cache TTL, tracker status, tracker hostname obfuscation,
  private directories not reachable, reverse-proxy client headers.
- **PHP** (8): version, PHP-FPM, `memory_limit`,
  `max_execution_time`, `post_max_size`, OPcache, required extensions,
  `shell_exec` / `proc_open`.
- **Database** (14): database size, tracking volume, MySQL / MariaDB
  version, InnoDB engine, `utf8mb4` charset, `max_allowed_packet`,
  `wait_timeout`, `innodb_flush_log_at_trx_commit`, InnoDB buffer
  pool, Transitions indexes, database not exposed, slow query log, SSD
  tuning, MySQL user privileges.
- **`config.ini.php` settings** (12): `force_ssl`, `trusted_hosts`,
  `cors_domains`, login brute-force protection, password complexity,
  auto-update, multi-server mode, unique visitors for ranges and years,
  custom reports max dimensions, MariaDB schema, admin IP allowlist.
- **Matomo general settings** (4): custom logo, TrackingSpamPrevention
  filters, CORS domains in sync with the UI, timezone consistency
  between PHP, MySQL and Matomo.
- **Backups & monitoring** (1): application logs written to a
  persistent file.

Each finding carries a **severity** (critical, high, medium, low,
info), a **status** (pass, fail, warn, skip), the **observed value**,
the **expected value**, a **recommendation** and, when relevant, a
configuration snippet.

## Premium checks

96 more checks are listed in the report with a "Premium" badge. They
are not run by the free plugin:

- **Privacy / GDPR ([premium](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit))**: IP and geolocation anonymization, data retention, PII removal, third-party cookies, Live reports, Heatmaps and Session Recording.
- **Users & permissions ([premium](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit))**: super user count, 2FA, anonymous access, dormant and shared accounts, API token scope, Tag Manager roles, ActivityLog.
- **Web sites ([premium](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit))**: URLs, excluded IPs and query parameters, e-commerce, site search, timezones, currencies, duplicates, cross-domain tracking.
- **Data quality ([premium](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit))**: goals, event naming, Custom Dimensions, segments, custom reports, funnels, alerts, annotations, Search Console, tracking failures.
- **Matomo Tag Manager ([premium](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit))**: containers, environments, Matomo tag, naming conventions, per-environment site ID.
- **Plugins ([premium](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit))**: plugin inventory, invalid, deprecated and outdated plugins, recommended official plugins, QueuedTracking, FormAnalytics.
- **Public file exposure ([premium](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit))**: tracker files, opt-out endpoints, favicon and heatmap configuration endpoint, through read-only HTTP probes.
- **High traffic / performance ([premium](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit))**: archiving settings, PHP-FPM workers, MySQL connections, QueuedTracking with Redis, CDN for tracker assets, segments and custom reports counts.
- **Multi-server / HA ([premium](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit))**: shared database, read replica, load balancer, dedicated archiver.
- **HTTP probes ([premium](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit))**: TLS certificate validity, CDN/WAF, HTTP/2 and HTTPS redirect checks of the Infrastructure & server category.

[Purchase Openmost Audit Premium version](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit)

## Key features

- **Read-only**: never writes to `config.ini.php`, to the database or
  to any file, and never stores the results.
- **No outbound HTTP calls**, no telemetry.
- **In-app report** under *Administration > Diagnostic > Audit*, for
  super users, with filters by category, severity, status and a
  free-text search.
- **Markdown export**: one click downloads the report as a `.md` file,
  ready to paste into a Word, Notion or Confluence deliverable. Add
  `&plain=1` to the export URL for ASCII status markers instead of
  emoji.
- **Console command**: `php console audit:run` prints the report as a
  table, `--format=markdown` as Markdown, `--only=<id>,<id>` restricts
  it to some checks.
- **7 languages**: English, French, German, Chinese (Simplified),
  Italian, Spanish and Swedish.

## Requirements

Matomo 6, PHP 8.1 or higher, MySQL 8.0+ or MariaDB 10.6+.

## Installation

Install the plugin from the Matomo Marketplace (*Administration >
Marketplace*), then open *Administration > Diagnostic > Audit* as a
super user.

If the premium version (AuditPremium) is also active, the free plugin
hides its menu entry and redirects to the premium report.

## Source code and support

The plugin is open source (GPL v3+): <https://github.com/openmost/Audit>.
Questions: [ronan@openmost.com](mailto:ronan@openmost.com).
