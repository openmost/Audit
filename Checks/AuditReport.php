<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks;

class AuditReport
{
    /**
     * @param CheckResult[] $results
     */
    public function __construct(
        public readonly array $results,
        public readonly string $generatedAt,
        public readonly string $matomoVersion,
        public readonly string $phpVersion,
        public readonly string $pluginVersion
    ) {
    }

    public function toArray(): array
    {
        $results = array_map(static fn(CheckResult $r) => $r->toArray(), $this->results);
        return [
            'generatedAt'   => $this->generatedAt,
            'matomoVersion' => $this->matomoVersion,
            'phpVersion'    => $this->phpVersion,
            'pluginVersion' => $this->pluginVersion,
            'summary'       => $this->summary(),
            'findings'      => $results,
        ];
    }

    public function summary(): array
    {
        $counts = [
            'total'    => 0,
            'pass'     => 0,
            'fail'     => 0,
            'warn'     => 0,
            'skip'     => 0,
            'premium'  => 0,
            'critical' => 0,
            'high'     => 0,
            'medium'   => 0,
            'low'      => 0,
            'info'     => 0,
        ];
        foreach ($this->results as $r) {
            $counts['total']++;
            if (isset($counts[$r->status])) {
                $counts[$r->status]++;
            }
            if (isset($counts[$r->severity])) {
                $counts[$r->severity]++;
            }
        }
        return $counts;
    }
}
