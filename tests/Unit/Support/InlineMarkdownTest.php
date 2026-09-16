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
use Piwik\Plugins\Audit\Support\InlineMarkdown;

class InlineMarkdownTest extends TestCase
{
    public function testConvertsBoldAndInlineCode(): void
    {
        $out = InlineMarkdown::toHtml('Set **memory_limit** to `2G` at minimum.');
        $this->assertStringContainsString('<strong>memory_limit</strong>', $out);
        $this->assertStringContainsString('<code>2G</code>', $out);
    }

    public function testConvertsEmphasisOnBothStylesButLeavesLonePunctuation(): void
    {
        $this->assertStringContainsString(
            '<em>italic</em>',
            InlineMarkdown::toHtml('This is *italic* text.')
        );
        $this->assertStringContainsString(
            '<em>italic</em>',
            InlineMarkdown::toHtml('This is _italic_ text.')
        );
        // Bare asterisks used for bullet lists should not turn into em tags.
        $this->assertStringNotContainsString(
            '<em>',
            InlineMarkdown::toHtml('bullet * not italic')
        );
    }

    public function testEscapesHtmlInInput(): void
    {
        $out = InlineMarkdown::toHtml('<script>alert(1)</script>');
        $this->assertStringNotContainsString('<script>', $out);
        $this->assertStringContainsString('&lt;script&gt;', $out);
    }

    public function testParagraphBreaksAndSoftBreaks(): void
    {
        $out = InlineMarkdown::toHtml("First line.\nsoft break.\n\nSecond paragraph.");
        $this->assertSame(2, substr_count($out, '<p>'));
        $this->assertStringContainsString('<br', $out);
    }

    public function testDoesNotCollideBoldAndItalicOnDoubleStars(): void
    {
        $out = InlineMarkdown::toHtml('**bold** and *italic*');
        $this->assertStringContainsString('<strong>bold</strong>', $out);
        $this->assertStringContainsString('<em>italic</em>', $out);
    }
}
