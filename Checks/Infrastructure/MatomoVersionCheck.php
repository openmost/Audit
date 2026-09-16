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

class MatomoVersionCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $current = class_exists(\Piwik\Version::class) ? \Piwik\Version::VERSION : 'unknown';
        $latest  = $this->resolveLatestVersion();
        $vars    = ['current' => $current, 'latest' => (string) $latest];

        if ($latest === null) {
            return $this->skip($item,
                detail: $this->t($item, 'unknown-latest', $vars, 'Could not determine the latest available Matomo version (no upstream data).'),
                currentValue: $current
            );
        }

        if (version_compare($current, $latest, '>=')) {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars, "Matomo is up to date ({$current})."),
                currentValue: $current,
                expectedValue: $latest
            );
        }

        return $this->fail($item,
            detail: $this->t($item, 'outdated', $vars, "Matomo {$current} is behind the latest stable {$latest}."),
            currentValue: $current,
            expectedValue: $latest,
            templateVars: $vars
        );
    }

    private function resolveLatestVersion(): ?string
    {
        if (class_exists(\Piwik\Plugins\CoreUpdater\Model\UpdateCheck::class)) {
            try {
                $latest = \Piwik\Plugins\CoreUpdater\Model\UpdateCheck::getLatestVersion();
                if (is_string($latest) && $latest !== '') {
                    return $latest;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
        if (class_exists(\Piwik\UpdateCheck::class)) {
            try {
                $latest = \Piwik\UpdateCheck::getLatestVersion();
                if (is_string($latest) && $latest !== '') {
                    return $latest;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
        return null;
    }
}
