<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Assertion;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Assertion\AssertionKind;

/**
 * @coversNothing
 */
#[CoversNothing]
final class AssertionKindTest extends TestCase
{
    public function testKeepsTheValuesTheDoctestPhpEnumCarried(): void
    {
        self::assertSame('return', AssertionKind::RETURN_VALUE);
        self::assertSame('output', AssertionKind::OUTPUT);
        self::assertSame('exception', AssertionKind::EXCEPTION);
    }
}
