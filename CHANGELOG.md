# Changelog

All notable changes to the Openmost Audit plugin are documented here.

## 5.1.0

- Fix: the OPcache check no longer reports a 0 MB memory pool when PHP returns inconsistent memory counters (negative values, seen on PHP 8.5). It falls back to the configured `opcache.memory_consumption`, and reports that the pool or the status cannot be determined (for example with `opcache.restrict_api`) instead of a false result.
- The Premium links of the report, the Markdown export and the documentation open the Audit page of openmost.com, which tracks the visits, instead of the shop.
- Requires Matomo 5.0.0 or higher (`>=5.0.0,<6.0.0-b1`). The theme variables used by the report fall back to the Matomo light theme colors when they are not available (before Matomo 5.10.0).
- Configuration snippets in the report follow the light or dark Matomo theme, including DarkTheme.
- 6 more languages: Arabic, Chinese (Traditional), Dutch, Japanese, Polish and Portuguese (13 in total).
- The package no longer ships internal files: the AI assistant notes (CLAUDE.md) are no longer tracked.

## 5.0.0

Matomo 5 edition of the free Openmost Audit, built from the 6.0.2 code
base. Same 53 checks, same report and same Markdown export, so an
instance can be put in order before upgrading to Matomo 6.

- Requires Matomo 5 (`>=5.0.0-b1,<6.0.0-b1`) and PHP 8.1 or higher.
- The PHP and database version checks keep the Matomo 6 thresholds
  (PHP 8.1+, MySQL 8.0+ / MariaDB 10.6+) but report them as warnings
  instead of failures: those versions still run Matomo 5, they only
  block the upgrade. Their messages say so, in the 7 languages.
- Vue bundle rebuilt with the Matomo 5 toolchain (vue-cli), as Matomo 5
  does not load the Vite output of the 6.x branch.

## 6.0.2

- Purchase links for AuditPremium (report page, Markdown export and documentation) now point to the Openmost shop.

## 6.0.1

- New Marketplace cover.

## 6.0.0

First release of the free edition of Openmost Audit, for Matomo 6.

- Read-only configuration audit of the Matomo instance: 53 automated
  checks across Infrastructure & server, PHP, Database,
  `config.ini.php` settings, Matomo general settings and Backups &
  monitoring. No write to the configuration, the database or any file,
  no outbound HTTP request, no telemetry.
- Admin report under Administration > Diagnostic > Audit, for super
  users, with filters by category, severity, status and free text.
- The 96 checks of the premium version (Privacy / GDPR, Users &
  permissions, Web sites, Data quality, Matomo Tag Manager, Plugins,
  Public file exposure, High traffic / performance, Multi-server / HA,
  and the TLS certificate, CDN/WAF, HTTP/2 and HTTPS redirect checks)
  are listed with a "Premium" badge and a link to the premium version,
  without being run.
- Markdown export of the report (with an ASCII-only variant through
  `&plain=1`), ending with the list of premium checks.
- Console commands `audit:run` (`--format=markdown`, `--only=`),
  `audit:debug-metrics` and `audit:debug-translations`.
- When AuditPremium is active, the plugin hides its menu entry,
  redirects its page to AuditPremium and leaves the console commands
  to AuditPremium.
- 7 languages: English, French, German, Chinese (Simplified), Italian,
  Spanish and Swedish.
- Requires Matomo 6 (`>=6.0.0-b1,<7.0.0-b1`), PHP 8.1+ and MySQL 8.0+
  or MariaDB 10.6+.
