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

class GeoipDbCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $providerClass = 'Piwik\\Plugins\\UserCountry\\LocationProvider';
        if (!class_exists($providerClass)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'UserCountry plugin is not available.'));
        }

        try {
            $currentId = (string) $providerClass::getCurrentProviderId();
        } catch (\Throwable $e) {
            return $this->skip($item, detail: $this->t($item, 'read-error', ['error' => $e->getMessage()],
                'Could not read the current location provider: ' . $e->getMessage()));
        }

        $vars = ['current' => $currentId];

        if ($currentId === '') {
            return $this->fail($item,
                detail: $this->t($item, 'none', $vars, 'No geolocation provider is configured.'),
                currentValue: '(none)',
                expectedValue: 'geoip2 (DBIP or MaxMind GeoLite2)'
            );
        }

        if (stripos($currentId, 'geoip2') !== false) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars, "Location provider is {$currentId}."),
                currentValue: $currentId,
                expectedValue: 'geoip2'
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'legacy', $vars, "Location provider is {$currentId}; GeoIP2 (DBIP or MaxMind GeoLite2) is recommended."),
            currentValue: $currentId,
            expectedValue: 'geoip2'
        );
    }
}
