<?php

namespace Piwik\Plugins\Audit\Checks\Infrastructure;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class GeoipFreshnessCheck extends AbstractCheck
{
    private const STALE_DAYS = 60;

    public function execute(ChecklistItem $item): CheckResult
    {
        $root = defined('PIWIK_DOCUMENT_ROOT') ? PIWIK_DOCUMENT_ROOT : realpath(__DIR__ . '/../../../../');
        if (!$root) {
            return $this->skip($item, detail: $this->t($item, 'root-unresolved', [], 'Document root unresolved.'));
        }
        $candidates = glob($root . '/misc/*.mmdb') ?: [];
        if (empty($candidates)) {
            return $this->skip($item, detail: $this->t($item, 'no-database', [], 'No .mmdb GeoIP database found in misc/.'));
        }
        $newest = 0;
        $file   = '';
        foreach ($candidates as $path) {
            $mt = @filemtime($path);
            if ($mt > $newest) { $newest = $mt; $file = $path; }
        }
        $days = (int) ((time() - $newest) / 86400);
        if ($days > self::STALE_DAYS) {
            return $this->warn($item,
                detail: $this->t($item, 'stale', ['days' => $days, 'file' => basename($file)], 'GeoIP database last updated {days} days ago ({file}). Refresh it monthly to keep country/city accuracy high.'),
                currentValue: "{$days} days old",
                expectedValue: '<= ' . self::STALE_DAYS . ' days'
            );
        }
        return $this->pass($item,
            detail: $this->t($item, 'pass', ['days' => $days, 'file' => basename($file)], 'GeoIP database updated {days} day(s) ago ({file}).'),
            currentValue: "{$days} days old",
            expectedValue: '<= ' . self::STALE_DAYS . ' days'
        );
    }
}
