<?php

namespace Piwik\Plugins\Audit\Checks\Infrastructure;

use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class CollectDomainObfuscatedCheck extends AbstractCheck
{
    private const BLOCKED_KEYWORDS = ['matomo', 'piwik', 'analytics', 'tracking', 'stats'];

    public function execute(ChecklistItem $item): CheckResult
    {
        $general = $this->readConfigSection('General');
        if ($general === null) {
            return $this->skip($item, detail: $this->t($item, 'config-unavailable', [], 'Piwik\\Config is not available.'));
        }
        $hosts = $general['trusted_hosts'] ?? [];
        if (!is_array($hosts)) $hosts = [$hosts];
        $hosts = array_values(array_filter(array_map('strval', $hosts), static fn($h) => trim($h) !== ''));
        if (empty($hosts)) {
            return $this->skip($item, detail: $this->t($item, 'no-trusted-hosts', [], 'No trusted_hosts configured.'));
        }
        $flagged = [];
        $clean   = [];
        foreach ($hosts as $h) {
            $isFlagged = false;
            foreach (self::BLOCKED_KEYWORDS as $kw) {
                if (stripos($h, $kw) !== false) {
                    $isFlagged = true;
                    break;
                }
            }
            if ($isFlagged) {
                $flagged[] = $h;
            } else {
                $clean[] = $h;
            }
        }

        // Pass as soon as ONE trusted host is neutral — visitors can be
        // pointed at the clean alias even if older hostnames remain
        // declared for legacy reasons.
        if (!empty($clean)) {
            if (!empty($flagged)) {
                $detail = $this->t($item, 'pass-with-flagged', [
                    'clean'   => implode('`, `', $clean),
                    'flagged' => implode('`, `', $flagged),
                ], 'At least one trusted host is neutral (`{clean}`). Other declared hosts still leak analytics keywords: `{flagged}`.');
            } else {
                $detail = $this->t($item, 'pass', ['clean' => implode('`, `', $clean)], 'At least one trusted host is neutral (`{clean}`).');
            }
            return $this->pass($item,
                detail: $detail,
                currentValue: implode(', ', $hosts),
                expectedValue: '>= 1 neutral hostname'
            );
        }

        return $this->warn($item,
            detail: $this->t($item, 'all-flagged', ['flagged' => implode('`, `', $flagged)],
                'All declared trusted hosts advertise an analytics-related domain: `{flagged}`. Ad-blockers typically block `matomo.`, `piwik.`, `analytics.` subdomains: declare at least one neutral alias.'),
            currentValue: implode(', ', $flagged),
            expectedValue: '>= 1 neutral hostname'
        );
    }
}
