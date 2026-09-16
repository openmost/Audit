<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks\Database;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checks\MetricsAwareInterface;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class MysqlMaxAllowedPacketCheck extends AbstractCheck implements MetricsAwareInterface
{
    private const BASELINE_MB = 512;
    private const LARGE_DB_MB = 1024;

    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Db::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Db is not available in this context.'));
        }

        try {
            $row = \Piwik\Db::fetchRow("SHOW VARIABLES LIKE 'max_allowed_packet'");
        } catch (\Throwable $e) {
            return $this->skip($item, detail: $this->t($item, 'read-error', ['error' => $e->getMessage()],
                'Could not query max_allowed_packet: ' . $e->getMessage()));
        }

        $bytes = (int) ($row['Value'] ?? 0);
        if ($bytes <= 0) {
            return $this->skip($item, detail: $this->t($item, 'no-data', [], 'max_allowed_packet could not be read.'));
        }

        $mb      = (int) round($bytes / 1024 / 1024);
        $display = $this->formatMb($mb);

        $isLargeDb = $this->metrics?->isLargeDb() ?? false;
        $targetMb  = $isLargeDb ? self::LARGE_DB_MB : self::BASELINE_MB;
        $target    = $this->formatMb($targetMb);
        $dbGb      = $this->metrics?->getDbSizeGb() ?? 0;
        $vars      = ['current' => $display, 'target' => $target, 'dbGb' => $dbGb];

        if ($mb >= $targetMb) {
            $variant = $isLargeDb ? 'pass-large' : 'pass';
            return $this->pass($item,
                detail: $this->t($item, $variant, $vars, "max_allowed_packet is {$display}."),
                currentValue: $display,
                expectedValue: '>= ' . $target
            );
        }

        $variant = $isLargeDb ? 'too-low-large' : 'too-low';
        return $this->warn($item,
            detail: $this->t($item, $variant, $vars,
                "max_allowed_packet is {$display}, below the recommended {$target}."),
            currentValue: $display,
            expectedValue: '>= ' . $target
        );
    }

    private function formatMb(int $mb): string
    {
        return $mb >= 1024 ? round($mb / 1024, 1) . 'G' : $mb . 'M';
    }
}
