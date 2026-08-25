<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Scanner;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Scanner\TargetKind;

/**
 * @covers \Toolkit\Doctest\Scanner\TargetKind
 */
#[CoversClass(TargetKind::class)]
final class TargetKindTest extends TestCase
{
    public function testKeepsTheValuesTheDoctestPhpEnumCarried(): void
    {
        self::assertSame('file', TargetKind::FILE);
        self::assertSame('class', TargetKind::CLASS_LIKE);
        self::assertSame('method', TargetKind::METHOD);
        self::assertSame('function', TargetKind::FUNCTION);
    }
}
