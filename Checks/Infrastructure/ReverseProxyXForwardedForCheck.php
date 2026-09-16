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

class ReverseProxyXForwardedForCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        $general = $this->readConfigSection('General');
        if ($general === null) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }

        $headers = $general['proxy_client_headers'] ?? [];
        if (!is_array($headers)) {
            $headers = [$headers];
        }
        $headers = array_values(array_filter(array_map('strval', $headers), static fn($h) => trim($h) !== ''));

        $proxyIps = $general['proxy_ips'] ?? [];
        if (!is_array($proxyIps)) {
            $proxyIps = [$proxyIps];
        }

        $seesXff = isset($_SERVER['HTTP_X_FORWARDED_FOR']);

        if (!$seesXff) {
            return $this->skip($item,
                detail: $this->t($item, 'no-proxy', [], 'No X-Forwarded-For header on this request: instance is likely not behind a reverse proxy.'),
                currentValue: 'no XFF seen'
            );
        }

        if (empty($headers)) {
            return $this->fail($item,
                detail: $this->t($item, 'missing-config', [],
                    'The request carries X-Forwarded-For but proxy_client_headers[] is empty. Matomo will log the proxy IP, not the visitor.'),
                currentValue: '(empty)',
                expectedValue: 'HTTP_X_FORWARDED_FOR'
            );
        }

        return $this->pass($item,
            detail: $this->t($item, 'pass', ['headers' => implode(', ', $headers), 'proxies' => count($proxyIps)],
                'Reverse proxy is configured: ' . implode(', ', $headers) . ' (' . count($proxyIps) . ' trusted proxy IP(s))'),
            currentValue: implode(',', $headers),
            expectedValue: 'proxy headers declared'
        );
    }
}
