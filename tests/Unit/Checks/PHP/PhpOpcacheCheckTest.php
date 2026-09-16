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

/**
 * Smoke test — we can't swap `opcache_get_status()` in a pure unit test,
 * but the class should always return a well-formed CheckResult with a
 * known status string regardless of the opcache state on the CI runner.
 */
class PhpOpcacheCheckTest extends TestCase
{
    public function testReturnsValidCheckResult(): void
    {
        $item = ChecklistItem::fromArray([
            'id' => 'srv-php-opcache', 'cat' => 'infrastructure', 'severity' => 'medium',
            'title' => 'OPcache', 'methods' => ['srv'],
        ]);
        $check  = new PhpOpcacheCheck();
        $result = $check->execute($item);

        $this->assertInstanceOf(CheckResult::class, $result);
        $this->assertContains($result->status, [
            CheckResult::STATUS_PASS,
            CheckResult::STATUS_FAIL,
            CheckResult::STATUS_WARN,
            CheckResult::STATUS_SKIP,
        ]);
        $this->assertSame('srv-php-opcache', $result->itemId);
    }
}
