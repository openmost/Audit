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
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checks\CheckRunner;
use Piwik\Plugins\Audit\Checklist\ChecklistLoader;
use Piwik\Plugins\Audit\Export\MarkdownExporter;

class RunAudit extends ConsoleCommand
{
    protected function configure(): void
    {
        $this->setName('audit:run');
        $this->setDescription('Run the Openmost Audit against this Matomo instance.');
        $this->addOptionalValueOption(
            'format',
            null,
            'Output format: table (default) or markdown.',
            'table'
        );
        $this->addOptionalValueOption(
            'only',
            null,
            'Comma-separated list of checklist item ids to run. Defaults to all.',
            null
        );
    }

    protected function doExecute(): int
    {
        $input  = $this->getInput();
        $output = $this->getOutput();

        $onlyRaw = (string) ($input->getOption('only') ?? '');
        $only    = $onlyRaw === '' ? [] : array_map('trim', explode(',', $onlyRaw));

        $runner = new CheckRunner();
        $report = $runner->run($only);

        if ($input->getOption('format') === 'markdown') {
            $metadata = (new ChecklistLoader())->loadMetadata();
            $labels   = [];
            foreach (($metadata['categories'] ?? []) as $cat) {
                if (!empty($cat['id'])) {
                    $labels[$cat['id']] = $cat['label'] ?? $cat['id'];
                }
            }
            $output->write((new MarkdownExporter($labels))->render($report));
            return 0;
        }

        $summary = $report->summary();
        $output->writeln(sprintf(
            '<info>Openmost Audit</info>: matomo %s, php %s, plugin %s',
            $report->matomoVersion,
            $report->phpVersion,
            $report->pluginVersion
        ));
        $output->writeln(sprintf(
            'Summary: %d total | %d pass | %d fail | %d warn | %d skip | %d premium (not run)',
            $summary['total'],
            $summary['pass'],
            $summary['fail'],
            $summary['warn'],
            $summary['skip'],
            $summary['premium']
        ));
        $output->writeln('');

        foreach ($report->results as $r) {
            if ($r->status === CheckResult::STATUS_PREMIUM) {
                continue;
            }
            $output->writeln(sprintf(
                ' %-6s %-8s %-35s %s',
                strtoupper($r->status),
                $r->severity,
                $r->itemId,
                $r->detail
            ));
        }

        return 0;
    }
}
