<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks\General;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class TrackingSpamOptionsCheck extends AbstractCheck
{
    private const SETTINGS_CLASS = 'Piwik\\Plugins\\TrackingSpamPrevention\\SystemSettings';

    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Plugin\Manager::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Plugin\\Manager is not available in this context.'));
        }

        $active = \Piwik\Plugin\Manager::getInstance()->getLoadedPluginsName();
        if (!in_array('TrackingSpamPrevention', $active, true) || !class_exists(self::SETTINGS_CLASS)) {
            return $this->skip($item,
                detail: $this->t($item, 'plugin-missing', [],
                    'TrackingSpamPrevention plugin is not installed, see plg-tracking-spam-prevention.'),
                currentValue: 'plugin missing'
            );
        }

        // The filters are system settings of the plugin (plugin_setting table, or a config file
        // override), read through the settings object so both sources are honoured.
        try {
            $settings = \Piwik\Container\StaticContainer::get(self::SETTINGS_CLASS);
            $filters  = [
                'cloud'    => $this->readBool($settings, 'block_clouds'),
                'headless' => $this->readBool($settings, 'blockHeadless'),
                'servers'  => $this->readBool($settings, 'blockServerSideLibraries'),
            ];
        } catch (\Throwable $e) {
            return $this->skip($item,
                detail: $this->t($item, 'read-error', ['error' => $e->getMessage()],
                    'Could not read TrackingSpamPrevention settings: ' . $e->getMessage()),
                currentValue: 'unknown'
            );
        }

        $enabled = count(array_filter($filters));
        $missing = array_keys(array_filter($filters, static fn(bool $on) => !$on));
        $vars    = ['enabled' => $enabled, 'missing' => implode(', ', $missing)];

        if ($enabled === 3) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars, 'All three TrackingSpamPrevention filters are enabled.'),
                currentValue: '3/3',
                expectedValue: '3/3'
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'partial', $vars,
                "{$enabled}/3 TrackingSpamPrevention filters enabled. Missing: " . implode(', ', $missing)),
            currentValue: "{$enabled}/3",
            expectedValue: '3/3'
        );
    }

    private function readBool(object $settings, string $property): bool
    {
        if (!isset($settings->$property) || !is_object($settings->$property)) {
            throw new \RuntimeException("setting {$property} not found");
        }

        return (bool) $settings->$property->getValue();
    }
}
