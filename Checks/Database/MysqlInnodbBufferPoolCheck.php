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

class MysqlInnodbBufferPoolCheck extends AbstractCheck implements MetricsAwareInterface
{
    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Db::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Db is not available in this context.'));
        }

        try {
            $row = \Piwik\Db::fetchRow("SHOW VARIABLES LIKE 'innodb_buffer_pool_size'");
        } catch (\Throwable $e) {
            return $this->skip($item, detail: $this->t($item, 'read-error', ['error' => $e->getMessage()],
                'Could not query innodb_buffer_pool_size: ' . $e->getMessage()));
        }

        $bytes = isset($row['Value']) ? (int) $row['Value'] : 0;
        if ($bytes <= 0) {
            return $this->skip($item, detail: $this->t($item, 'no-data', [], 'innodb_buffer_pool_size could not be read.'));
        }

        $mb      = (int) round($bytes / 1024 / 1024);
        $display = $this->formatMb($mb);

        $dbBytes  = $this->metrics?->getDbSizeBytes() ?? 0;
        $targetMb = max(1024, (int) round($dbBytes / 1024 / 1024 * 0.5));
        $dbGb     = $this->metrics?->getDbSizeGb() ?? 0;
        $target   = $this->formatMb($targetMb);
        $vars     = ['current' => $display, 'target' => $target, 'dbGb' => $dbGb];

        if ($mb >= $targetMb) {
            $variant = $dbBytes > 0 ? 'pass-sized' : 'pass';
            return $this->pass($item,
                detail: $this->t($item, $variant, $vars,
                    "innodb_buffer_pool_size is {$display}"
                    . ($dbBytes > 0 ? ", sized for the current {$dbGb} GB Matomo schema." : '.')
                ),
                currentValue: $display,
                expectedValue: '>= ' . $target
            );
        }

        if ($mb < $targetMb / 2) {
            return $this->fail($item,
                detail: $this->t($item, 'way-too-low', $vars,
                    "innodb_buffer_pool_size is {$display}, far below the recommended {$target} for the current {$dbGb} GB Matomo schema."
                ),
                currentValue: $display,
                expectedValue: '>= ' . $target
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'too-low', $vars,
                "innodb_buffer_pool_size is {$display}, below the recommended {$target} for the current {$dbGb} GB Matomo schema."
            ),
            currentValue: $display,
            expectedValue: '>= ' . $target
        );
    }

    private function formatMb(int $mb): string
    {
        return $mb >= 1024 ? round($mb / 1024, 1) . 'G' : $mb . 'M';
    }
}
