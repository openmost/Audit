<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit;

class Audit extends \Piwik\Plugin
{
    /** Plugin page on openmost.com, with the purchase of AuditPremium, which runs the premium checks. */
    public const PREMIUM_URL = 'https://openmost.com/matomo/extensions/audit?utm_source=matomo_onpremise&utm_medium=plugin&utm_campaign=premium_upgrade&utm_content=audit';

    public function registerEvents(): array
    {
        return [
            'Template.afterEventsReport' => 'renderOpenmostCommunicationAfterEvents',
            'Widget.filterWidgets' => 'addOpenmostCommunicationWidgets',
            'Template.beforeContent' => 'renderOpenmostCommunication',
            'AssetManager.getStylesheetFiles'        => 'getStylesheetFiles',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'Console.filterCommands'                 => 'filterConsoleCommands',
        ];
    }

    /**
     * AuditPremium ships every check of this plugin. When it is active this
     * plugin steps aside: no menu entry, page redirected, no console command.
     */
    public static function isPremiumActivated(): bool
    {
        return \Piwik\Plugin\Manager::getInstance()->isPluginActivated('AuditPremium');
    }

    /**
     * Both plugins declare `audit:run`: leave the command names to
     * AuditPremium when it is active.
     *
     * @param string[] $commands
     */
    public function filterConsoleCommands(array &$commands): void
    {
        if (!self::isPremiumActivated()) {
            return;
        }
        $commands = array_values(array_filter(
            $commands,
            static fn(string $class) => !str_starts_with($class, 'Piwik\\Plugins\\Audit\\')
        ));
    }

    public function getStylesheetFiles(&$stylesheets): void
    {
        $stylesheets[] = 'plugins/Audit/stylesheets/audit.less';
    }

    /**
     * Register the Audit_* UI translation keys so they are available to the
     * Vue front-end via translate(). Finding texts (`<Slug>Title`,
     * `<Slug>Template`, `<Slug>Detail*`) are resolved server-side by
     * CheckTranslator and would only bloat the translations Matomo sends
     * with every page, so they are left out.
     */
    public function getClientSideTranslationKeys(array &$translationKeys): void
    {
        $langFile = __DIR__ . '/lang/en.json';
        if (!is_readable($langFile)) {
            return;
        }
        $decoded = json_decode((string) file_get_contents($langFile), true);
        if (!is_array($decoded) || empty($decoded['Audit']) || !is_array($decoded['Audit'])) {
            return;
        }
        foreach (array_keys($decoded['Audit']) as $key) {
            if (preg_match('/^(Srv|Cfg|Net|Prv|Sec|Data|Site|Mtm|Ht|Ms|Plg|Gen|Ops)[A-Z0-9].*(Title|Template|Detail[A-Za-z0-9]*)$/', $key)) {
                continue;
            }
            $translationKeys[] = 'Audit_' . $key;
        }
    }

    public function renderOpenmostCommunication(&$out, $layout, $module = '', $action = '')
    {
        OpenmostCommunication::beforeContent($out, (string) $layout, (string) $module, (string) $action, $this->getPluginName());
    }

    public function addOpenmostCommunicationWidgets($list)
    {
        OpenmostCommunication::filterWidgets($list, $this->getPluginName());
    }

    public function renderOpenmostCommunicationAfterEvents(&$out, $dataTable = null)
    {
        OpenmostCommunication::afterEventsReport($out, $this->getPluginName());
    }
}
