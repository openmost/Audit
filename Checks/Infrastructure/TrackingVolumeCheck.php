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
use Piwik\Plugins\Audit\Checks\MetricsAwareInterface;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class TrackingVolumeCheck extends AbstractCheck implements MetricsAwareInterface
{
    public function execute(ChecklistItem $item): CheckResult
    {
        if ($this->metrics === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Instance metrics service is not available.'));
        }

        $visits  = $this->metrics->getTotalVisits();
        $actions = $this->metrics->getTotalActions();
        $monthly = $this->metrics->getMonthlyActions();
        $sizing  = $this->metrics->getInstanceSizing();

        if ($visits === 0 && $actions === 0) {
            return $this->skip($item,
                detail: $this->t($item, 'no-data', [], 'No tracking volume found in log_visit / log_link_visit_action.'),
                currentValue: 'unknown'
            );
        }

        $sizingLabel = match ($sizing) {
            'xl' => 'XL (>= 5M actions / month)',
            'l'  => 'L (>= 1M actions / month)',
            'm'  => 'M (>= 250k actions / month)',
            's'  => 'S (< 250k actions / month)',
            default => $sizing,
        };

        $vars = [
            'visits'      => number_format($visits, 0, '.', ' '),
            'actions'     => number_format($actions, 0, '.', ' '),
            'monthly'     => number_format($monthly, 0, '.', ' '),
            'sizing'      => $sizing,
            'sizingLabel' => $sizingLabel,
        ];

        $fallback = "Total visits: **{$vars['visits']}** · Total actions: **{$vars['actions']}** · "
                  . "Last 30 days: **{$vars['monthly']}** actions. Sizing tier: *{$vars['sizingLabel']}*.";

        return $this->pass($item,
            detail: $this->t($item, 'pass', $vars, $fallback),
            currentValue: $vars['monthly'] . ' actions / 30d',
            expectedValue: 'informational'
        );
    }
}
