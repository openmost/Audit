<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks\Infrastructure;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class FilePermissionsCheck extends AbstractCheck
{
    /**
     * Directories Matomo must be able to write to at runtime. Covers the
     * tracker cache (`tmp/`), the tag-manager JS bundles (`js/`) and the
     * plugin install-time storage (`config/`, for credential overrides).
     */
    private const REQUIRED_WRITABLE = ['tmp', 'js', 'config'];

    /**
     * Files that must stay read-only for the PHP process. A writable
     * `index.php` or `matomo.php` typically means the whole web-root is
     * mounted with 0777 — a common footgun.
     */
    private const MUST_NOT_BE_WRITABLE = ['index.php', 'matomo.php'];

    public function execute(ChecklistItem $item): CheckResult
    {
        $root = defined('PIWIK_DOCUMENT_ROOT') ? PIWIK_DOCUMENT_ROOT : realpath(__DIR__ . '/../../../../');
        if (!is_string($root) || $root === '' || !is_dir($root)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Could not resolve Matomo document root.'));
        }

        $notWritable = [];
        $missing     = [];
        foreach (self::REQUIRED_WRITABLE as $relative) {
            $path = $root . DIRECTORY_SEPARATOR . $relative;
            if (!file_exists($path)) {
                $missing[] = $relative;
                continue;
            }
            if (!is_writable($path)) {
                $notWritable[] = $relative;
            }
        }

        $overlyWritable = [];
        foreach (self::MUST_NOT_BE_WRITABLE as $relative) {
            $path = $root . DIRECTORY_SEPARATOR . $relative;
            if (file_exists($path) && is_writable($path)) {
                $overlyWritable[] = $relative;
            }
        }

        if (!empty($missing)) {
            return $this->fail($item,
                detail: $this->t($item, 'missing', ['dirs' => implode(', ', $missing)],
                    'Missing required directories: ' . implode(', ', $missing)),
                currentValue: 'missing: ' . implode(', ', $missing),
                expectedValue: 'tmp/, js/ and config/ present and writable'
            );
        }

        if (!empty($notWritable)) {
            return $this->fail($item,
                detail: $this->t($item, 'not-writable', ['dirs' => implode(', ', $notWritable)],
                    'Directories are not writable by the PHP process: ' . implode(', ', $notWritable)),
                currentValue: 'not writable: ' . implode(', ', $notWritable),
                expectedValue: 'tmp/, js/ and config/ writable'
            );
        }

        if (!empty($overlyWritable)) {
            return $this->warn($item,
                detail: $this->t($item, 'too-open', ['files' => implode(', ', $overlyWritable)],
                    'Entry-point files are writable by the PHP process: ' . implode(', ', $overlyWritable) . '. This is typical of a 0777 document root.'),
                currentValue: 'writable: ' . implode(', ', $overlyWritable),
                expectedValue: 'entry points read-only'
            );
        }

        return $this->pass($item,
            detail: $this->t($item, 'pass', [], 'tmp/, js/ and config/ are writable; entry-point files are read-only.'),
            currentValue: 'ok',
            expectedValue: 'writable runtime dirs, read-only entry points'
        );
    }
}
