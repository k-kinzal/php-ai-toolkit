<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Assertion;

use PhpAiToolkit\Doctest\Assertion\Assertion;
use PhpAiToolkit\Doctest\Assertion\AssertionKind;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\Doctest\Assertion\Assertion
 */
#[CoversClass(Assertion::class)]
final class AssertionTest extends TestCase
{
    public function testCarriesAReturnValueAssertion(): void
    {
        $assertion = new Assertion(AssertionKind::RETURN_VALUE, '42');

        self::assertSame(AssertionKind::RETURN_VALUE, $assertion->type);
        self::assertSame('42', $assertion->expectedRaw);
        self::assertNull($assertion->exceptionMessage);
    }

    public function testCarriesAnExceptionAssertionWithItsMessage(): void
    {
        $assertion = new Assertion(AssertionKind::EXCEPTION, 'InvalidArgumentException', 'Value must be positive');

        self::assertSame(AssertionKind::EXCEPTION, $assertion->type);
        self::assertSame('Value must be positive', $assertion->exceptionMessage);
    }
}
