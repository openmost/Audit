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

class CheckResult
{
    public const STATUS_PASS = 'pass';
    public const STATUS_FAIL = 'fail';
    public const STATUS_WARN = 'warn';
    public const STATUS_SKIP = 'skip';

    /** Listed only: the check runs in AuditPremium. */
    public const STATUS_PREMIUM = 'premium';

    public const SEV_CRITICAL = 'critical';
    public const SEV_HIGH     = 'high';
    public const SEV_MEDIUM   = 'medium';
    public const SEV_LOW      = 'low';
    public const SEV_INFO     = 'info';

    /**
     * @param string[] $references
     */
    public function __construct(
        public readonly string $itemId,
        public readonly string $title,
        public readonly string $category,
        public readonly string $status,
        public readonly string $severity,
        public readonly string $detail = '',
        public readonly ?string $currentValue = null,
        public readonly ?string $expectedValue = null,
        public readonly ?string $recommendation = null,
        public readonly ?string $codeSnippet = null,
        public readonly array $references = []
    ) {
    }

    public function toArray(): array
    {
        return [
            'itemId'         => $this->itemId,
            'title'          => $this->title,
            'category'       => $this->category,
            'status'         => $this->status,
            'severity'       => $this->severity,
            'detail'         => $this->detail,
            'currentValue'   => $this->currentValue,
            'expectedValue'  => $this->expectedValue,
            'recommendation' => $this->recommendation,
            'codeSnippet'    => $this->codeSnippet,
            'references'     => $this->references,
        ];
    }
}
