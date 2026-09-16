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

namespace Piwik\Plugins\Audit\tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\Audit\Checklist\ChecklistItem;
use Piwik\Plugins\Audit\Support\CheckTranslator;

class CheckTranslatorTest extends TestCase
{
    public function testSlugConvertsKebabCaseToPascalCase(): void
    {
        $this->assertSame('SrvPhpVersion',        CheckTranslator::slug('srv-php-version'));
        $this->assertSame('CfgForceSsl',          CheckTranslator::slug('cfg-force-ssl'));
        $this->assertSame('HtMysqlMaxConnections', CheckTranslator::slug('ht-mysql-max-connections'));
    }

    public function testFallsBackToYamlTitleWhenTranslationIsMissing(): void
    {
        $item = ChecklistItem::fromArray([
            'id'    => 'srv-nonexistent-item',
            'cat'   => 'infrastructure',
            'title' => 'YAML fallback title',
        ]);

        // Piwik\Piwik is not loaded in the standalone test bootstrap,
        // so the translator must return the YAML fallback rather than
        // the translation key.
        $this->assertSame('YAML fallback title', CheckTranslator::title($item));
    }

    public function testTemplateFallsBackToYamlWhenTranslationIsMissing(): void
    {
        $item = ChecklistItem::fromArray([
            'id'       => 'srv-nonexistent-item',
            'cat'      => 'infrastructure',
            'title'    => 'Fake item',
            'template' => 'Raw template with {current}.',
        ]);

        $this->assertSame('Raw template with {current}.', CheckTranslator::template($item));
    }

    public function testTemplateReturnsNullWhenYamlHasNoTemplate(): void
    {
        $item = ChecklistItem::fromArray([
            'id'    => 'srv-nonexistent-item',
            'cat'   => 'infrastructure',
            'title' => 'Fake item',
        ]);

        $this->assertNull(CheckTranslator::template($item));
    }
}
