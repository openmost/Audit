## FAQ

### What does the free plugin check?

53 automated checks in 6 categories: Infrastructure & server, PHP,
Database, `config.ini.php` settings, Matomo general settings and
Backups & monitoring. Each finding gives a status (pass, fail, warn,
skip), a severity, the observed and expected values and a
recommendation.

### What are the checks with a "Premium" badge?

The report also lists 96 checks that only run in the
[premium version](https://openmost.com/matomo/extensions/audit?utm_source=matomo_installed_plugin&utm_medium=plugin_audit_documentation&utm_campaign=plugin_premium_audit):
Privacy / GDPR, Users & permissions, Web sites, Data quality, Matomo
Tag Manager, Plugins, Public file exposure, High traffic / performance
and Multi-server / HA, plus 4 Infrastructure & server checks that need
HTTP probes (TLS certificate, CDN/WAF, HTTP/2, HTTPS redirect). The
free plugin shows their title and severity but does not run them, so
they have no result. Select "Premium" in the status filter to list
only them.

### Does the plugin modify anything on my instance?

No. The plugin is read-only: it reads `config.ini.php`, runs read
queries (`SELECT`, `SHOW`) against the database and inspects the PHP
runtime. It never writes to `config.ini.php`, to the database or to
any file, and it does not store the results: every report is computed
when you open the page or run the command.

### Does it make outbound HTTP calls or send data to a third party?

No. The free plugin makes no HTTP request and has no telemetry. The
HTTP probes (TLS certificate, CDN/WAF, HTTP/2, HTTPS redirect, public
file exposure) are a premium feature.

### Who can run an audit?

Only super users. The menu entry is hidden for other users and every
page and export requires super user access.

### How do I export the report?

Click **Export Markdown** on the report page to download a `.md` file
with the instance information, a summary and the findings grouped by
category. Add `&plain=1` to the export URL to replace the emoji status
badges with `[PASS]`, `[FAIL]`, `[WARN]`, `[SKIP]` and `[PREMIUM]`.
From the command line, use `php console audit:run --format=markdown > audit.md`.

The export contains the results of the free checks, followed by the
list of premium checks (title, id and severity) without any result.

### Can I run it from the command line?

Yes:

- `php console audit:run` prints the results as a table.
- `php console audit:run --format=markdown` prints the Markdown report.
- `php console audit:run --only=srv-php-version,cfg-force-ssl` runs
  only the given checks.

`audit:debug-metrics` and `audit:debug-translations` are helpers for
troubleshooting.

### What happens if both Audit and AuditPremium are installed?

AuditPremium runs every check of the free plugin. When it is active,
the free plugin steps aside: its menu entry is hidden, its page
redirects to the AuditPremium report and the `audit:*` console
commands are the AuditPremium ones. You can deactivate the free
plugin.

### Which languages are available?

English, French, German, Chinese (Simplified), Italian, Spanish and
Swedish. The report follows the language of the Matomo user.

### Which Matomo versions are supported?

Matomo 6, with PHP 8.1 or higher and MySQL 8.0+ or MariaDB 10.6+. The
version checks follow the Matomo 6 requirements: PHP 8.2 or later is
recommended, as PHP 8.1 no longer receives security fixes.

### Where can I get help?

- GitHub issues: <https://github.com/openmost/Audit/issues>
- Email: [ronan@openmost.com](mailto:ronan@openmost.com)
