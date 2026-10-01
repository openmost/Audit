<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @group Plugins
 * @group Audit
 */

namespace Piwik\Plugins\Audit\tests\Unit\Checks\PHP;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checks\PHP\PhpOpcacheCheck;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class PhpOpcacheCheckTest extends TestCase
{
    private const MB = 1048576;

    public function testReturnsValidCheckResult(): void
    {
        $result = (new PhpOpcacheCheck())->execute($this->item());

        $this->assertInstanceOf(CheckResult::class, $result);
        $this->assertContains($result->status, [
            CheckResult::STATUS_PASS,
            CheckResult::STATUS_FAIL,
            CheckResult::STATUS_WARN,
            CheckResult::STATUS_SKIP,
        ]);
        $this->assertSame('srv-php-opcache', $result->itemId);
    }

    public function testPassesWithAValidMemoryReport(): void
    {
        $result = $this->evaluate($this->status(100 * self::MB, 400 * self::MB, 12 * self::MB), true, 512);

        $this->assertSame(CheckResult::STATUS_PASS, $result->status);
        $this->assertSame('100M used / 512M total', $result->currentValue);
    }

    public function testWarnsWhenThePoolIsTooSmall(): void
    {
        $result = $this->evaluate($this->status(60 * self::MB, 68 * self::MB, 0), true, 128);

        $this->assertSame(CheckResult::STATUS_WARN, $result->status);
        $this->assertSame('128M', $result->currentValue);
    }

    public function testFallsBackToTheConfiguredPoolWhenTheCountersAreNegative(): void
    {
        // Counters reported by PHP 8.5.0 after OPcache restarts: they add up to 0.
        $result = $this->evaluate($this->status(-190147288, 166799168, 23348120), true, 512);

        $this->assertSame(CheckResult::STATUS_PASS, $result->status);
        $this->assertSame('512M total', $result->currentValue);
    }

    public function testWarnsOnASmallConfiguredPoolWhenTheCountersAreUnusable(): void
    {
        $result = $this->evaluate(['opcache_enabled' => true], true, 128);

        $this->assertSame(CheckResult::STATUS_WARN, $result->status);
        $this->assertSame('128M', $result->currentValue);
    }

    public function testCannotDetermineThePoolWithoutCountersNorSetting(): void
    {
        $result = $this->evaluate($this->status(0, 0, 0), true, null);

        $this->assertSame(CheckResult::STATUS_SKIP, $result->status);
        $this->assertSame('unknown', $result->currentValue);
        $this->assertStringNotContainsString('0 MB', $result->detail);
    }

    public function testCannotDetermineTheStatusWhenItIsRestricted(): void
    {
        $result = $this->evaluate(false, true, 512);

        $this->assertSame(CheckResult::STATUS_SKIP, $result->status);
        $this->assertSame('unknown', $result->currentValue);
    }

    public function testFailsWhenOpcacheIsDisabled(): void
    {
        $this->assertSame(CheckResult::STATUS_FAIL, $this->evaluate(false, false, 512)->status);
        $this->assertSame(CheckResult::STATUS_FAIL, $this->evaluate(['opcache_enabled' => false], true, 512)->status);
    }

    /**
     * @param array|false $status
     */
    private function evaluate($status, bool $enabledSetting, ?int $configuredPoolMb): CheckResult
    {
        return (new PhpOpcacheCheck())->evaluate($this->item(), $status, $enabledSetting, $configuredPoolMb);
    }

    private function status(int $used, int $free, int $wasted): array
    {
        return [
            'opcache_enabled' => true,
            'memory_usage' => [
                'used_memory' => $used,
                'free_memory' => $free,
                'wasted_memory' => $wasted,
            ],
        ];
    }

    private function item(): ChecklistItem
    {
        return ChecklistItem::fromArray([
            'id' => 'srv-php-opcache', 'cat' => 'infrastructure', 'severity' => 'medium',
            'title' => 'OPcache', 'methods' => ['srv'],
        ]);
    }
}
