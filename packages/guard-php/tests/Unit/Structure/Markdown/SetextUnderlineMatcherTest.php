<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Markdown;

use Guard\Structure\Markdown\SetextUnderlineMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Markdown\SetextUnderlineMatcher
 */
#[CoversClass(SetextUnderlineMatcher::class)]
final class SetextUnderlineMatcherTest extends TestCase
{
    public function testLevelRecognizesEqualsAndDashUnderlines(): void
    {
        self::assertSame(1, (new SetextUnderlineMatcher())->level('==='));
        self::assertSame(1, (new SetextUnderlineMatcher())->level('=  '));
        self::assertSame(2, (new SetextUnderlineMatcher())->level('---'));
        self::assertSame(2, (new SetextUnderlineMatcher())->level('-'));
    }

    public function testLevelRejectsMixedOrSpacedMarkers(): void
    {
        self::assertNull((new SetextUnderlineMatcher())->level('=-='));
        self::assertNull((new SetextUnderlineMatcher())->level('- - -'));
        self::assertNull((new SetextUnderlineMatcher())->level('|---|---|'));
    }
}
