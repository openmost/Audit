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
use Piwik\Plugins\Audit\Checklist\ChecklistItem;
use Piwik\Plugins\Audit\Checks\PHP\PhpVersionCheck;

class PhpVersionCheckTest extends TestCase
{
    private function item(): ChecklistItem
    {
        return ChecklistItem::fromArray([
            'id'       => 'srv-php-version',
            'cat'      => 'php',
            'title'    => 'PHP >= 8.2',
            'severity' => 'high',
        ]);
    }

    private function checkFor(string $version): PhpVersionCheck
    {
        return new class ($version) extends PhpVersionCheck {
            public function __construct(private string $version)
            {
            }

            protected function currentVersion(): string
            {
                return $this->version;
            }
        };
    }

    /**
     * @dataProvider versions
     */
    public function testStatusPerPhpVersion(string $version, string $expectedStatus): void
    {
        $result = $this->checkFor($version)->execute($this->item());
        $this->assertSame($expectedStatus, $result->status);
        $this->assertSame($version, $result->currentValue);
    }

    public static function versions(): array
    {
        return [
            'recommended'      => ['8.4.3', 'pass'],
            'oldest supported' => ['8.2.0', 'pass'],
            'eol but runs 6'   => ['8.1.29', 'warn'],
            'below matomo 6'   => ['8.0.30', 'warn'],
            'php 7.4'          => ['7.4.33', 'warn'],
        ];
    }

    /**
     * On this Matomo 5 branch a PHP version below the Matomo 6 minimum is a
     * warning, not a failure: it still runs Matomo 5, it only blocks the
     * upgrade. The message has to say so.
     */
    public function testBelowMatomo6MinimumWarnsAboutTheUpgrade(): void
    {
        $result = $this->checkFor('8.0.30')->execute($this->item());

        $this->assertSame('warn', $result->status);
        $this->assertStringContainsString('Matomo 5', $result->detail);
        $this->assertStringContainsString('Matomo 6 minimum (8.1)', $result->detail);
    }
}
