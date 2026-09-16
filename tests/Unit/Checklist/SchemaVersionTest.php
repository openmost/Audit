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

class SchemaVersionTest extends TestCase
{
    public function testRejectsFutureSchemaVersion(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'yaml_') . '.yaml';
        file_put_contents(
            $path,
            "version: 99\nitems:\n  - id: dummy\n    cat: test\n    severity: info\n"
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/schema version 99/');

        try {
            (new ChecklistLoader($path))->loadItems();
        } finally {
            @unlink($path);
        }
    }

    public function testRejectsItemsWithInvalidSeverity(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'yaml_') . '.yaml';
        file_put_contents(
            $path,
            "version: 1\nitems:\n" .
            "  - id: ok-item\n    cat: test\n    severity: high\n" .
            "  - id: bad-item\n    cat: test\n    severity: bogus\n"
        );

        $loader = new ChecklistLoader($path);
        $items  = $loader->loadItems();

        $this->assertCount(1, $items, 'Item with invalid severity must be dropped.');
        $this->assertSame('ok-item', $items[0]->id);
        $this->assertNotEmpty($loader->getWarnings(), 'Loader should have recorded the skip.');

        @unlink($path);
    }

    public function testAcceptsFileWithoutVersionHeader(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'yaml_') . '.yaml';
        file_put_contents(
            $path,
            "items:\n  - id: legacy\n    cat: test\n    severity: low\n"
        );

        $items = (new ChecklistLoader($path))->loadItems();
        $this->assertCount(1, $items);
        $this->assertSame('legacy', $items[0]->id);

        @unlink($path);
    }
}
