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

namespace Piwik\Plugins\Audit\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\Audit\Checklist\ChecklistLoader;
use Piwik\Plugins\Audit\Checks\CheckRunner;
use Piwik\Plugins\Audit\Support\CheckTranslator;

/**
 * The premium checks only run in AuditPremium. This plugin may list them,
 * but must not ship anything that would let them run or reveal how they
 * work: no class, no detection rule, no recommendation, no detail message.
 */
class PremiumLeakTest extends TestCase
{
    private const PLUGIN_DIR = __DIR__ . '/../..';

    /** Keys allowed on a premium item of checklist.yaml. */
    private const PREMIUM_ITEM_KEYS = ['id', 'cat', 'severity', 'premium'];

    /** Categories whose checks run in this plugin. */
    private const FREE_CATEGORIES = ['infrastructure', 'php', 'database', 'config-file', 'general', 'ops'];

    /** Checks of the free categories that need HTTP probes, a premium feature. */
    private const HTTP_CHECKS = ['srv-ssl-cert-rating', 'srv-waf-cdn', 'srv-web-http2', 'srv-web-https-force'];

    /**
     * @return array<string, bool> item id => premium flag
     */
    private function items(): array
    {
        $items = [];
        foreach ((new ChecklistLoader(self::PLUGIN_DIR . '/Checklist/checklist.yaml'))->loadItems() as $item) {
            $items[$item->id] = $item->premium;
        }
        return $items;
    }

    public function testSplitMatchesTheFreeCategories(): void
    {
        $loader = new ChecklistLoader(self::PLUGIN_DIR . '/Checklist/checklist.yaml');
        foreach ($loader->loadItems() as $item) {
            $free = in_array($item->category, self::FREE_CATEGORIES, true)
                && !in_array($item->id, self::HTTP_CHECKS, true);
            $this->assertSame(!$free, $item->premium, "{$item->id} has the wrong premium flag");
        }
    }

    public function testPremiumItemsHaveNoDetectionRules(): void
    {
        $loader = new ChecklistLoader(self::PLUGIN_DIR . '/Checklist/checklist.yaml');
        foreach ($loader->loadItems() as $item) {
            if (!$item->premium) {
                continue;
            }
            $this->assertSame([], $item->methods, "{$item->id} has methods");
            $this->assertSame([], $item->rules, "{$item->id} has detection rules");
            $this->assertSame([], $item->refs, "{$item->id} has references");
            $this->assertNull($item->code, "{$item->id} has a code snippet");
            $this->assertNull($item->template, "{$item->id} has a template");
        }

        preg_match_all('/^  - id: ([\w-]+)\n((?:    .*\n)*)/m', str_replace("\r\n", "\n", (string) file_get_contents(self::PLUGIN_DIR . '/Checklist/checklist.yaml')), $blocks, PREG_SET_ORDER);
        foreach ($blocks as $block) {
            if (!str_contains($block[2], 'premium: true')) {
                continue;
            }
            preg_match_all('/^    (\w+):/m', $block[2], $keys);
            $this->assertSame([], array_diff($keys[1], self::PREMIUM_ITEM_KEYS), "{$block[1]} has extra YAML keys");
        }
    }

    public function testNoClassForPremiumItems(): void
    {
        $runner = (new \ReflectionClass(CheckRunner::class))->newInstanceWithoutConstructor();
        foreach ($this->items() as $id => $premium) {
            $class = $runner->resolveCheckClass($id);
            if ($premium) {
                $this->assertTrue($class === null || !class_exists($class), "{$id} has a check class");
            } else {
                $this->assertNotNull($class, "{$id} has no check class");
                $this->assertTrue(class_exists($class), "{$id}: {$class} is missing");
            }
        }

        $classFiles = glob(self::PLUGIN_DIR . '/Checks/*/*Check.php') ?: [];
        $this->assertCount(count(array_filter($this->items(), static fn(bool $p) => !$p)), $classFiles);
    }

    public function testPremiumItemsOnlyHaveATitleInLanguageFiles(): void
    {
        $premiumSlugs = [];
        foreach ($this->items() as $id => $premium) {
            if ($premium) {
                $premiumSlugs[] = CheckTranslator::slug($id);
            }
        }

        foreach (glob(self::PLUGIN_DIR . '/lang/*.json') ?: [] as $file) {
            $keys = array_keys(json_decode((string) file_get_contents($file), true)['Audit']);
            foreach ($premiumSlugs as $slug) {
                $this->assertContains($slug . 'Title', $keys, basename($file) . " misses {$slug}Title");
                foreach ($keys as $key) {
                    $this->assertFalse(
                        str_starts_with($key, $slug . 'Template') || str_starts_with($key, $slug . 'Detail'),
                        basename($file) . " ships premium text {$key}"
                    );
                }
            }
        }
    }

    public function testNoHttpProbesNorPremiumCode(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::PLUGIN_DIR, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = str_replace('\\', '/', (string) $file);
            if (!preg_match('/\.(php|vue|ts|twig|json|yaml)$/', $path) || str_contains($path, '/node_modules/') || str_ends_with($path, 'PremiumLeakTest.php')) {
                continue;
            }
            $content = (string) file_get_contents($path);
            $this->assertStringNotContainsString('HttpProber', $content, "{$path} references HttpProber");
            $this->assertStringNotContainsString('AuditPremium_', $content, "{$path} references AuditPremium keys");
            $this->assertStringNotContainsString('Plugins\\AuditPremium', $content, "{$path} references AuditPremium code");
        }
    }
}
