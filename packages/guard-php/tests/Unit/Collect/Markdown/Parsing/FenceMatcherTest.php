<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Markdown\Parsing;

use Guard\Collect\Markdown\Parsing\Fence;
use Guard\Collect\Markdown\Parsing\FenceMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Markdown\Parsing\FenceMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Fence
 */
#[CoversClass(FenceMatcher::class)]
#[UsesClass(Fence::class)]
final class FenceMatcherTest extends TestCase
{
    public function testOpenRecognizesBacktickAndTildeFences(): void
    {
        $backtick = (new FenceMatcher())->open('````php');
        $tilde = (new FenceMatcher())->open('~~~ `info`');

        self::assertNotNull($backtick);
        self::assertSame('`', $backtick->marker);
        self::assertSame(4, $backtick->length);
        self::assertNotNull($tilde);
        self::assertSame('~', $tilde->marker);
        self::assertSame(3, $tilde->length);
    }

    public function testOpenRejectsShortFencesAndInlineCode(): void
    {
        self::assertNull((new FenceMatcher())->open('``not a fence'));
        self::assertNull((new FenceMatcher())->open('```inline``` code'));
        self::assertNull((new FenceMatcher())->open('text'));
    }

    public function testClosesRequiresSameMarkerAtLeastAsLong(): void
    {
        $fence = new Fence('`', 4);

        self::assertTrue((new FenceMatcher())->closes($fence, '````'));
        self::assertTrue((new FenceMatcher())->closes($fence, '    `````  '));
        self::assertFalse((new FenceMatcher())->closes($fence, '```'));
        self::assertFalse((new FenceMatcher())->closes($fence, '~~~~'));
        self::assertFalse((new FenceMatcher())->closes($fence, '```` php'));
    }
}
