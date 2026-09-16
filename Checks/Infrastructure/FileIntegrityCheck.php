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

class FileIntegrityCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\FileIntegrity::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\FileIntegrity is not available in this context.'));
        }

        try {
            $result = \Piwik\FileIntegrity::getFileIntegrityInformation();
        } catch (\Throwable $e) {
            return $this->skip($item, detail: $this->t($item, 'read-error', ['error' => $e->getMessage()],
                'Could not run file integrity check: ' . $e->getMessage()));
        }

        $success = (bool) array_shift($result);
        if ($success) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', [], 'File integrity check passed.'),
                currentValue: 'ok',
                expectedValue: 'ok'
            );
        }

        $count = count($result);
        return $this->fail($item,
            detail: $this->t($item, 'issues', ['count' => $count], "{$count} file integrity issue(s) reported by Matomo."),
            currentValue: (string) $count . ' issue(s)',
            expectedValue: 'ok'
        );
    }
}
