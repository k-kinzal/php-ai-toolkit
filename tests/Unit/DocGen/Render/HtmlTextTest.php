<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Render\HtmlText;

/**
 * @covers \Toolkit\DocGen\Render\HtmlText
 */
#[CoversClass(HtmlText::class)]
final class HtmlTextTest extends TestCase
{
    public function testEEscapesQuotesAnglesAndAmpersand(): void
    {
        self::assertSame('&lt;a href=&quot;x&quot;&gt;&amp;&#039;&lt;/a&gt;', (new HtmlText())->e('<a href="x">&\'</a>'));
    }

    public function testELeavesPlainTextUnchanged(): void
    {
        self::assertSame('plain text', (new HtmlText())->e('plain text'));
    }

    public function testESubstitutesInvalidUtf8Bytes(): void
    {
        self::assertSame("a\u{FFFD}(b", (new HtmlText())->e("a\xC3(b"));
    }
}
