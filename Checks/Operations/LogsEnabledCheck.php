<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks\Operations;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class LogsEnabledCheck extends AbstractCheck
{
    /**
     * Writers that actually persist log entries outside the request
     * lifecycle. `screen` is considered non-persistent because its output
     * is only visible to whoever triggered the request — useless for
     * ops-side forensics after the fact.
     */
    private const PERSISTENT_WRITERS = ['file', 'database', 'errorlog', 'syslog'];

    public function execute(ChecklistItem $item): CheckResult
    {
        $log = $this->readConfigSection('log');
        if ($log === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }

        $writers = $log['log_writers'] ?? [];
        if (!is_array($writers)) {
            $writers = [$writers];
        }
        $writers = array_values(array_filter(
            array_map(static fn($w) => strtolower(trim((string) $w)), $writers),
            static fn(string $w) => $w !== ''
        ));

        $level = strtoupper((string) ($log['log_level'] ?? 'WARN'));
        // Matomo's default when the key is unset — mirror it explicitly
        // so the recommendation can reference the effective path.
        $filePath = trim((string) ($log['logger_file_path'] ?? 'tmp/logs/matomo.log'));

        if (empty($writers)) {
            return $this->fail($item,
                detail: $this->t($item, 'no-writers', [],
                    '[log] log_writers is empty. Matomo errors are not persisted; ops-side forensics will be impossible.'),
                currentValue: '(none)',
                expectedValue: 'log_writers[] = file'
            );
        }

        $persistent = array_values(array_intersect($writers, self::PERSISTENT_WRITERS));
        if (empty($persistent)) {
            // The stock global.ini.php ships `log_writers[] = screen` only.
            // That is fine for interactive debugging but invisible in
            // production — we surface it as a warning with a concrete fix.
            return $this->warn($item,
                detail: $this->t($item, 'screen-only', ['writers' => implode(', ', $writers)],
                    'Logging is configured with `' . implode(', ', $writers) . '` only. Matomo writes to stdout/stderr, which is not persisted on most installations. Add a `file`, `database`, or `syslog` writer.'),
                currentValue: implode(',', $writers) . ' @ ' . $level,
                expectedValue: 'log_writers[] = file'
            );
        }

        // `file` is the recommended default in Matomo's documentation —
        // easy to tail, compatible with logrotate, no extra DB load. We
        // pass on any persistent writer but flag the file-path explicitly
        // when file logging is active so the consultant can verify it.
        $hasFile = in_array('file', $writers, true);
        if ($hasFile) {
            return $this->pass($item,
                detail: $this->t($item, 'pass-file', [
                    'writers' => implode(', ', $writers),
                    'level'   => $level,
                    'path'    => $filePath,
                ],
                    'File logging active (writers: {writers}, level={level}, path={path}).'),
                currentValue: 'file → ' . $filePath . ' @ ' . $level,
                expectedValue: 'file → /path/to/matomo.log'
            );
        }

        return $this->pass($item,
            detail: $this->t($item, 'pass', ['writers' => implode(', ', $persistent), 'level' => $level],
                'Logging is persisted via {writers} (level={level}).'),
            currentValue: implode(',', $writers) . ' @ ' . $level,
            expectedValue: 'file / database / errorlog / syslog'
        );
    }
}
