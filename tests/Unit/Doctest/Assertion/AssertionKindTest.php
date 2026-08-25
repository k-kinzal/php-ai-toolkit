<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Assertion;

use PhpAiToolkit\Doctest\Assertion\AssertionKind;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\Doctest\Assertion\AssertionKind
 */
#[CoversClass(AssertionKind::class)]
final class AssertionKindTest extends TestCase
{
    public function testKeepsTheValuesTheDoctestPhpEnumCarried(): void
    {
        self::assertSame('return', AssertionKind::RETURN_VALUE);
        self::assertSame('output', AssertionKind::OUTPUT);
        self::assertSame('exception', AssertionKind::EXCEPTION);
    }
}
