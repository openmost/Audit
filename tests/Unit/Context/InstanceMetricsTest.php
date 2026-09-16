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

namespace Piwik\Plugins\Audit\tests\Unit\Context;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\Audit\Context\InstanceMetrics;

class InstanceMetricsTest extends TestCase
{
    public function testEveryGetterDegradesGracefully(): void
    {
        $metrics = new InstanceMetrics();

        // Whether Matomo's classes are on the autoloader or not, every
        // getter must return a defined type without throwing. The
        // values may be real if a DB is reachable; what matters is that
        // a missing environment never crashes a check.
        $this->assertIsInt($metrics->getDbSizeBytes());
        $this->assertIsFloat($metrics->getDbSizeGb());
        $this->assertIsInt($metrics->getTotalActions());
        $this->assertIsInt($metrics->getTotalVisits());
        $this->assertIsInt($metrics->getMonthlyActions());
        $this->assertContains($metrics->getInstanceSizing(), ['s', 'm', 'l', 'xl']);
        $this->assertIsBool($metrics->isHighTraffic());
        $this->assertIsBool($metrics->isLargeDb());
        $this->assertIsArray($metrics->getLoadedPlugins());
        $this->assertIsBool($metrics->isPluginActive('Live'));
        $this->assertIsArray($metrics->getGeneralConfig());
    }

    public function testSizingTransitionsCrossExpectedThresholds(): void
    {
        $metrics = new class extends InstanceMetrics {
            public int $stub = 0;
            public function getMonthlyActions(): int
            {
                return $this->stub;
            }
        };

        $metrics->stub = 0;
        $this->assertSame('s', $metrics->getInstanceSizing());

        $metrics->stub = 250_000;
        $this->assertSame('m', $metrics->getInstanceSizing());

        $metrics->stub = 1_000_000;
        $this->assertSame('l', $metrics->getInstanceSizing());

        $metrics->stub = 5_000_000;
        $this->assertSame('xl', $metrics->getInstanceSizing());
    }
}
