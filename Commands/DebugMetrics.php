<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\Audit\Context\InstanceMetrics;

class DebugMetrics extends ConsoleCommand
{
    protected function configure(): void
    {
        $this->setName('audit:debug-metrics');
        $this->setDescription('Dump the InstanceMetrics snapshot (for debugging).');
    }

    protected function doExecute(): int
    {
        $output  = $this->getOutput();
        $metrics = new InstanceMetrics();

        foreach ($metrics->snapshot() as $key => $value) {
            $output->writeln(sprintf(' %-20s %s', $key, (string) $value));
        }

        // Dump raw log table counts for comparison
        try {
            $rows = \Piwik\Db::fetchAll(
                "SELECT table_name, TABLE_ROWS
                   FROM information_schema.tables
                  WHERE table_schema = DATABASE()
                    AND (table_name LIKE '%log_visit%' OR table_name LIKE '%log_link%')
                  ORDER BY table_name"
            );
            $output->writeln('');
            $output->writeln('<info>Raw log tables (information_schema):</info>');
            foreach ($rows as $row) {
                $name = $row['table_name'] ?? $row['TABLE_NAME'] ?? '?';
                $r    = $row['TABLE_ROWS']  ?? $row['table_rows']  ?? '?';
                $output->writeln(sprintf('  %-40s rows=%s', $name, $r));
            }
        } catch (\Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        return 0;
    }
}
