<?php

declare(strict_types=1);

namespace Tests\Fixture\NoBrokenCodeExpectation;

use Exception;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class WithBehaviorExpectation extends TestCase
{
    public function testExpectsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
    }

    public function testExpectsCheckedException(): void
    {
        $this->expectException(JsonException::class);
    }

    public function testExpectsRootException(): void
    {
        $this->expectException(Exception::class);
    }

    public function testExpectsRuntimeExceptionObject(): void
    {
        $this->expectExceptionObject(new RuntimeException('Report source is unreadable.'));
    }

    public function testExpectsDynamicType(string $expected): void
    {
        $this->expectException($expected);
    }

    public function testExpectsMessageOnly(): void
    {
        $this->expectExceptionMessage('Report source is unreadable.');
    }
}
