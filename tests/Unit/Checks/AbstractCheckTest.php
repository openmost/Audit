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

namespace Piwik\Plugins\Audit\tests\Unit\Checks;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\Audit\Checks\AbstractCheck;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class AbstractCheckTest extends TestCase
{
    private function makeItem(array $overrides = []): ChecklistItem
    {
        return ChecklistItem::fromArray(array_merge([
            'id'       => 'srv-php-version',
            'cat'      => 'infrastructure',
            'title'    => 'PHP >= 8.2',
            'severity' => 'high',
            'methods'  => ['diag'],
            'template' => 'Current PHP is {current}, expected {expected}.',
            'code'     => 'php_version=8.2',
            'refs'     => ['https://example.test'],
        ], $overrides));
    }

    private function subject(): AbstractCheck
    {
        return new class extends AbstractCheck {
            public function execute(ChecklistItem $item): CheckResult
            {
                return $this->pass($item, detail: 'ok');
            }

            public function callPass(...$args): CheckResult { return $this->pass(...$args); }
            public function callFail(...$args): CheckResult { return $this->fail(...$args); }
            public function callWarn(...$args): CheckResult { return $this->warn(...$args); }
            public function callSkip(...$args): CheckResult { return $this->skip(...$args); }
        };
    }

    public function testPassResultDoesNotRenderRecommendationOrSnippet(): void
    {
        $check  = $this->subject();
        $item   = $this->makeItem();
        $result = $check->callPass($item, 'all good', '8.3');

        $this->assertSame('pass', $result->status);
        $this->assertSame('all good', $result->detail);
        $this->assertNull($result->recommendation);
        $this->assertNull($result->codeSnippet);
    }

    public function testFailRendersTemplatedRecommendationAndExposesSnippet(): void
    {
        $check  = $this->subject();
        $item   = $this->makeItem();
        $result = $check->callFail(
            $item,
            'php too old',
            '8.0',
            '>= 8.2'
        );

        $this->assertSame('fail', $result->status);
        $this->assertSame('8.0',  $result->currentValue);
        $this->assertSame('>= 8.2', $result->expectedValue);
        $this->assertIsString($result->recommendation);
        $this->assertStringContainsString('Current PHP is 8.0', $result->recommendation);
        $this->assertStringContainsString('expected >= 8.2',    $result->recommendation);
        $this->assertSame('php_version=8.2', $result->codeSnippet);
        $this->assertSame(['https://example.test'], $result->references);
    }

    public function testWarnCarriesTemplateAndCode(): void
    {
        $check  = $this->subject();
        $item   = $this->makeItem();
        $result = $check->callWarn($item, 'close enough', '8.1');

        $this->assertSame('warn', $result->status);
        $this->assertNotNull($result->recommendation);
        $this->assertSame('php_version=8.2', $result->codeSnippet);
    }

    public function testSkipResultHasNoRecommendation(): void
    {
        $check  = $this->subject();
        $item   = $this->makeItem();
        $result = $check->callSkip($item, 'not applicable here');

        $this->assertSame('skip', $result->status);
        $this->assertNull($result->recommendation);
        $this->assertNull($result->codeSnippet);
    }
}
