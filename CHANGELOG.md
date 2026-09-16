# Changelog

All notable changes to the Openmost Audit plugin are documented here.

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
