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
use Piwik\Plugins\Audit\Checklist\ChecklistLoader;
use Piwik\Plugins\Audit\Checks\CheckInterface;
use Piwik\Plugins\Audit\Checks\CheckResult;
use Piwik\Plugins\Audit\Checks\CheckRunner;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;

class CheckRunnerTest extends TestCase
{
    public function testResolvesNamespaceFromIdPrefix(): void
    {
        $runner = new CheckRunner(new ChecklistLoader(__DIR__ . '/../../../Checklist/checklist.yaml'));

        $this->assertSame(
            'Piwik\\Plugins\\Audit\\Checks\\PHP\\PhpVersionCheck',
            $runner->resolveCheckClass('srv-php-version')
        );
        $this->assertSame(
            'Piwik\\Plugins\\Audit\\Checks\\Database\\MysqlVersionCheck',
            $runner->resolveCheckClass('srv-mysql-version')
        );
        $this->assertSame(
            'Piwik\\Plugins\\Audit\\Checks\\ConfigFile\\CorsDomainsCheck',
            $runner->resolveCheckClass('cfg-cors-domains')
        );
        $this->assertSame(
            'Piwik\\Plugins\\Audit\\Checks\\General\\TrackingSpamOptionsCheck',
            $runner->resolveCheckClass('gen-tracking-spam-options')
        );
        $this->assertSame(
            'Piwik\\Plugins\\Audit\\Checks\\Operations\\LogsEnabledCheck',
            $runner->resolveCheckClass('ops-logs-enabled')
        );
    }

    public function testPremiumItemsAreListedWithoutRunning(): void
    {
        $runner = new CheckRunner(new ChecklistLoader(__DIR__ . '/../../../Checklist/checklist.yaml'));

        $ran  = false;
        $fake = new class ($ran) implements CheckInterface {
            public function __construct(private bool &$ran)
            {
            }

            public function execute(ChecklistItem $item): CheckResult
            {
                $this->ran = true;
                return new CheckResult($item->id, $item->title, $item->category, CheckResult::STATUS_PASS, $item->severity);
            }
        };
        // Even an injected implementation must never run for a premium item.
        $runner->overrideCheck('prv-ip-anonymization', $fake);

        $results = $runner->run(['prv-ip-anonymization'])->results;

        $this->assertFalse($ran);
        $this->assertCount(1, $results);
        $this->assertSame(CheckResult::STATUS_PREMIUM, $results[0]->status);
        $this->assertSame('', $results[0]->detail);
        $this->assertNull($results[0]->recommendation);
    }

    public function testReportCountsFreeAndPremiumChecks(): void
    {
        $runner = new CheckRunner(new ChecklistLoader(__DIR__ . '/../../../Checklist/checklist.yaml'));
        $fake   = new class implements CheckInterface {
            public function execute(ChecklistItem $item): CheckResult
            {
                return new CheckResult($item->id, $item->title, $item->category, CheckResult::STATUS_PASS, $item->severity);
            }
        };
        $loader = new ChecklistLoader(__DIR__ . '/../../../Checklist/checklist.yaml');
        foreach ($loader->loadItems() as $item) {
            $runner->overrideCheck($item->id, $fake);
        }

        $summary = $runner->run()->summary();

        $this->assertSame(149, $summary['total']);
        $this->assertSame(53, $summary['pass']);
        $this->assertSame(96, $summary['premium']);
    }

    public function testReturnsNullForUnknownPrefix(): void
    {
        $runner = new CheckRunner(new ChecklistLoader(__DIR__ . '/../../../Checklist/checklist.yaml'));
        $this->assertNull($runner->resolveCheckClass('unk-foo-bar'));
    }

    public function testRunExecutesOverriddenCheck(): void
    {
        $runner = new CheckRunner(new ChecklistLoader(__DIR__ . '/../../../Checklist/checklist.yaml'));

        $fake = new class implements CheckInterface {
            public function execute(ChecklistItem $item): CheckResult
            {
                return new CheckResult(
                    itemId: $item->id,
                    title: $item->title,
                    category: $item->category,
                    status: CheckResult::STATUS_PASS,
                    severity: $item->severity,
                    detail: 'forced pass'
                );
            }
        };
        $runner->overrideCheck('srv-php-version', $fake);

        $report  = $runner->run(['srv-php-version']);
        $results = $report->results;

        $this->assertCount(1, $results);
        $this->assertSame('srv-php-version', $results[0]->itemId);
        $this->assertSame('pass', $results[0]->status);
        $this->assertSame('forced pass', $results[0]->detail);
    }
}
