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

namespace Piwik\Plugins\Audit\tests\Unit\Checklist;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\Audit\Checklist\ChecklistLoader;

class ChecklistLoaderTest extends TestCase
{
    private function loader(): ChecklistLoader
    {
        return new ChecklistLoader(__DIR__ . '/../../../Checklist/checklist.yaml');
    }

    public function testLoadsAllItemsFromTheBundledChecklist(): void
    {
        $items = $this->loader()->loadItems();
        // manual / client interview items were dropped in 1.0.0, the checklist is fully automated
        $this->assertGreaterThanOrEqual(149, count($items));
    }

    public function testEachItemHasCoreFields(): void
    {
        // Titles and templates now live in lang/*.json — the YAML holds
        // only structural metadata (id, category, severity, methods,
        // detection rules, references, code snippets).
        foreach ($this->loader()->loadItems() as $item) {
            $this->assertNotEmpty($item->id,       'id missing');
            $this->assertNotEmpty($item->category, 'category missing on ' . $item->id);
            $this->assertContains(
                $item->severity,
                ['critical', 'high', 'medium', 'low', 'info'],
                'invalid severity on ' . $item->id
            );
        }
    }

    public function testResolvesExpectedMvpItems(): void
    {
        $loader = $this->loader();
        $mvp    = [
            'srv-matomo-version',
            'srv-php-version',
            'srv-php-memory-limit',
            'srv-php-opcache',
            'srv-php-post-max-size',
            'srv-mysql-version',
            'srv-mysql-innodb-buffer-pool',
            'srv-mysql-indexes-transitions',
            'srv-mysql-engine-innodb',
            'cfg-cors-domains',
            'cfg-force-ssl',
            'cfg-multi-server-environment',
            'plg-tracking-spam-prevention',
            'plg-deprecated-disabled',
            'prv-ip-anonymization',
        ];
        foreach ($mvp as $id) {
            $this->assertNotNull($loader->findById($id), "Missing MVP item: {$id}");
        }
    }

    public function testLoadsMetadata(): void
    {
        $meta = $this->loader()->loadMetadata();
        $this->assertArrayHasKey('categories', $meta);
        $this->assertArrayHasKey('severities', $meta);
        $this->assertNotEmpty($meta['categories']);
    }
}
