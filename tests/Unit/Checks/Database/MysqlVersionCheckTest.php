<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\tests\Unit\Checks\Database;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\Audit\Checks\Database\MysqlVersionCheck;

class MysqlVersionCheckTest extends TestCase
{
    /**
     * @dataProvider versions
     */
    public function testNormalizesServerVersions(string $raw, string $expected): void
    {
        $this->assertSame($expected, MysqlVersionCheck::normalizeVersion($raw));
    }

    public static function versions(): array
    {
        return [
            'mysql'                  => ['8.4.7', '8.4.7'],
            'mysql with suffix'      => ['8.0.36-0ubuntu0.22.04.1', '8.0.36'],
            'mariadb'                => ['10.6.12-MariaDB-log', '10.6.12'],
            'mariadb protocol prefix' => ['5.5.5-10.6.12-MariaDB', '10.6.12'],
            'mariadb 11'             => ['11.4.2-MariaDB', '11.4.2'],
        ];
    }

    public function testMinimumsFollowMatomo6(): void
    {
        $this->assertSame('8.0.0', MysqlVersionCheck::MIN_MYSQL);
        $this->assertSame('10.6.0', MysqlVersionCheck::MIN_MARIADB);
        $this->assertTrue(version_compare(MysqlVersionCheck::normalizeVersion('5.7.44'), MysqlVersionCheck::MIN_MYSQL, '<'));
        $this->assertTrue(version_compare(MysqlVersionCheck::normalizeVersion('10.5.23-MariaDB'), MysqlVersionCheck::MIN_MARIADB, '<'));
    }
}
