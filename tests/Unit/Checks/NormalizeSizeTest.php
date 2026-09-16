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

class NormalizeSizeTest extends TestCase
{
    private function subject(): AbstractCheck
    {
        return new class extends AbstractCheck {
            public function execute(ChecklistItem $item): CheckResult
            {
                return $this->pass($item);
            }

            public function call(string $raw): ?int
            {
                return $this->normalizeSize($raw);
            }
        };
    }

    /**
     * @dataProvider sizeProvider
     */
    public function testParsesIniStyleSizes(string $raw, ?int $expected): void
    {
        $this->assertSame($expected, $this->subject()->call($raw));
    }

    public static function sizeProvider(): array
    {
        return [
            'bytes no suffix'   => ['12345', 12345],
            'kilobytes'         => ['128K', 128 * 1024],
            'lowercase k'       => ['128k', 128 * 1024],
            'megabytes'         => ['256M', 256 * 1024 * 1024],
            'gigabytes'         => ['2G',   2 * 1024 * 1024 * 1024],
            'whitespace padded' => [' 64M ', 64 * 1024 * 1024],
            'unlimited -1'      => ['-1', null],
            'empty string'      => ['', null],
            'garbage'           => ['abc', null],
            'mixed'             => ['128MX', null],
        ];
    }
}
