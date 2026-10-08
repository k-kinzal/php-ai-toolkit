<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Definition;

use Guard\Policy\Definition\DeclaredHeading;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Definition\DeclaredHeading
 */
#[CoversClass(DeclaredHeading::class)]
final class DeclaredHeadingTest extends TestCase
{
    public function testGetExposesLevelAndText(): void
    {
        $heading = new DeclaredHeading(3, 'Options');

        self::assertSame(3, $heading->level);
        self::assertSame('Options', $heading->text);
    }

    public function testNotationRendersAtxHeading(): void
    {
        self::assertSame('### Options', (new DeclaredHeading(3, 'Options'))->notation());
        self::assertSame('#', (new DeclaredHeading(1, ''))->notation());
    }
}
