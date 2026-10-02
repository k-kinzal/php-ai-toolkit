<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Markdown;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Markdown\LineIndentation;

/**
 * @covers \Toolkit\DocGuard\Markdown\LineIndentation
 */
#[CoversClass(LineIndentation::class)]
final class LineIndentationTest extends TestCase
{
    public function testWidthCountsLeadingSpaces(): void
    {
        self::assertSame(0, (new LineIndentation())->width('# Title'));
        self::assertSame(3, (new LineIndentation())->width('   # Title'));
        self::assertSame(0, (new LineIndentation())->width(''));
    }

    public function testWidthExpandsTabsToTheNextTabStop(): void
    {
        self::assertSame(4, (new LineIndentation())->width("\tcode"));
        self::assertSame(4, (new LineIndentation())->width("  \tcode"));
        self::assertSame(8, (new LineIndentation())->width("     \tcode"));
    }
}
