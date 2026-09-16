<?php

namespace Piwik\Plugins\Audit\Checks\Infrastructure;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class NfsCodebaseCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $root = defined('PIWIK_DOCUMENT_ROOT') ? PIWIK_DOCUMENT_ROOT : realpath(__DIR__ . '/../../../../');
        if (!$root || !is_file('/proc/mounts')) {
            return $this->skip($item, detail: $this->t($item, 'mounts-unreadable', [], 'Cannot read /proc/mounts on this platform.'));
        }
        $mounts = @file_get_contents('/proc/mounts') ?: '';
        foreach (explode("\n", $mounts) as $line) {
            $parts = preg_split('/\s+/', $line);
            if (count($parts) < 3) continue;
            [$dev, $mount, $fs] = $parts;
            if (str_starts_with($root, $mount) && in_array($fs, ['nfs', 'nfs4'], true)) {
                return $this->warn($item,
                    detail: $this->t($item, 'nfs-mount', ['mount' => $mount, 'fs' => $fs], 'Matomo codebase lives on an NFS mount ({mount}, {fs}). Make sure OPcache and realpath cache are sized large enough to compensate for the NFS latency.'),
                    currentValue: "{$mount} ({$fs})",
                    expectedValue: 'local disk or tuned OPcache'
                );
            }
        }
        return $this->pass($item,
            detail: $this->t($item, 'pass', [], 'Matomo codebase is on a local filesystem.'),
            currentValue: 'local',
            expectedValue: 'local or tuned'
        );
    }
}
