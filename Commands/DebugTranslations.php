<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin — diagnostic command for translation pipeline.
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Commands;

use Piwik\Piwik;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\Audit\Checklist\ChecklistLoader;
use Piwik\Plugins\Audit\Support\CheckTranslator;
use Piwik\Translation\Translator;
use Piwik\Container\StaticContainer;

class DebugTranslations extends ConsoleCommand
{
    protected function configure(): void
    {
        $this->setName('audit:debug-translations');
        $this->setDescription('Dump raw translate() return values for every implemented check id.');
        $this->addOptionalValueOption('locale', null, 'Force a specific Matomo locale (e.g. fr, en).', null);
    }

    protected function doExecute(): int
    {
        $output = $this->getOutput();
        $input  = $this->getInput();

        $locale = $input->getOption('locale');
        if ($locale) {
            try {
                /** @var Translator $translator */
                $translator = StaticContainer::get(Translator::class);
                $translator->setCurrentLanguage($locale);
                $output->writeln("<info>Switched to locale: {$locale}</info>");
            } catch (\Throwable $e) {
                $output->writeln("<error>Could not switch locale: " . $e->getMessage() . "</error>");
            }
        }

        $ids = [
            'srv-php-memory-limit', 'srv-php-opcache', 'srv-file-integrity',
            'cfg-force-ssl', 'gen-tracking-spam-options', 'ops-logs-enabled',
        ];

        $loader = new ChecklistLoader();
        $items  = [];
        foreach ($loader->loadItems() as $item) {
            $items[$item->id] = $item;
        }

        foreach ($ids as $id) {
            $slug = CheckTranslator::slug($id);
            $titleKey   = 'Audit_' . $slug . 'Title';
            $tmplKey    = 'Audit_' . $slug . 'Template';
            $detailKey  = 'Audit_' . $slug . 'DetailPass';

            $output->writeln('<info>' . $id . '</info>');
            $output->writeln('  title-key: ' . $titleKey);
            $output->writeln('  title:     ' . Piwik::translate($titleKey));
            $output->writeln('  template:  ' . substr(Piwik::translate($tmplKey), 0, 80));
            $output->writeln('  detail:    ' . Piwik::translate($detailKey));
            $output->writeln('');
        }

        return 0;
    }
}
