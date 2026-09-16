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

class SmtpConfiguredCheck extends AbstractCheck
{
    public function execute(ChecklistItem $item): CheckResult
    {
        if (!class_exists(\Piwik\Config::class)) {
            return $this->skip($item, detail: $this->t($item, 'unavailable', [], 'Piwik\\Config is not available in this context.'));
        }

        $mail = \Piwik\Config::getInstance()->mail ?? [];
        $host = trim((string) ($mail['host'] ?? ''));
        $type = strtolower(trim((string) ($mail['transport'] ?? '')));
        $vars = ['type' => $type, 'host' => $host];

        if ($type === '' || $type === 'default') {
            return $this->warn($item,
                detail: $this->t($item, 'fallback', $vars,
                    'Mail transport falls back to PHP mail(). Configure SMTP explicitly for reliable delivery.'),
                currentValue: '(default php mail)',
                expectedValue: 'smtp'
            );
        }

        if ($type === 'smtp' && $host !== '') {
            return $this->pass($item,
                detail: $this->t($item, 'pass', $vars, "SMTP transport configured (host: {$host})."),
                currentValue: "smtp://{$host}",
                expectedValue: 'smtp'
            );
        }

        if ($type === 'smtp') {
            return $this->fail($item,
                detail: $this->t($item, 'smtp-no-host', $vars,
                    'Mail transport is set to SMTP but no host is configured.'),
                currentValue: 'smtp without host',
                expectedValue: 'smtp with host'
            );
        }

        return $this->pass($item,
            detail: $this->t($item, 'other', $vars, "Mail transport is {$type}."),
            currentValue: $type,
            expectedValue: 'smtp or a reliable transport'
        );
    }
}
