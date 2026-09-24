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
use Piwik\Plugins\Audit\Checklist\ChecklistItem;
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

    private function item(): ChecklistItem
    {
        return ChecklistItem::fromArray([
            'id'       => 'srv-mysql-version',
            'cat'      => 'database',
            'title'    => 'MySQL 8.0+ or MariaDB 10.6+',
            'severity' => 'high',
        ]);
    }

    private function checkFor(string $serverVersion): MysqlVersionCheck
    {
        return new class ($serverVersion) extends MysqlVersionCheck {
            public function __construct(private string $serverVersion)
            {
            }

            protected function readServerVersion(): string
            {
                return $this->serverVersion;
            }
        };
    }

    /**
     * @dataProvider serverVersions
     */
    public function testStatusPerServerVersion(string $serverVersion, string $expectedStatus): void
    {
        $this->assertSame($expectedStatus, $this->checkFor($serverVersion)->execute($this->item())->status);
    }

    public static function serverVersions(): array
    {
        return [
            'mysql 8.4'       => ['8.4.7', 'pass'],
            'mysql 8.0'       => ['8.0.36-0ubuntu0.22.04.1', 'pass'],
            'mysql 5.7'       => ['5.7.44', 'warn'],
            'mariadb 10.6'    => ['10.6.12-MariaDB-log', 'pass'],
            'mariadb 10.5'    => ['10.5.23-MariaDB', 'warn'],
        ];
    }

    /**
     * On this Matomo 5 branch a database below the Matomo 6 minimum is a
     * warning, not a failure: it still runs Matomo 5, it only blocks the
     * upgrade. The message has to say so.
     */
    public function testBelowMatomo6MinimumWarnsAboutTheUpgrade(): void
    {
        foreach (['5.7.44' => '(8.0)', '10.5.23-MariaDB' => '(10.6)'] as $version => $minimum) {
            $result = $this->checkFor($version)->execute($this->item());

            $this->assertSame('warn', $result->status);
            $this->assertStringContainsString('Matomo 5', $result->detail);
            $this->assertStringContainsString('Matomo 6 minimum ' . $minimum, $result->detail);
        }
    }
}
