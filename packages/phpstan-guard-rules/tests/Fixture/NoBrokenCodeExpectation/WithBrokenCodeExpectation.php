<?php

declare(strict_types=1);

namespace Tests\Fixture\NoBrokenCodeExpectation;

use DivisionByZeroError;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Throwable;
use TypeError;

final class WithBrokenCodeExpectation extends TestCase
{
    public function testExpectsThrowable(): void
    {
        $this->expectException(Throwable::class);
    }

    public function testExpectsLogicException(): void
    {
        $this->expectException(LogicException::class);
    }

    public function testExpectsLogicSubclassStatically(): void
    {
        self::expectException(InvalidArgumentException::class);
    }

    public function testExpectsError(): void
    {
        $this->expectException(TypeError::class);
    }

    public function testExpectsErrorObject(): void
    {
        $this->expectExceptionObject(new DivisionByZeroError('Division by zero'));
    }
}
