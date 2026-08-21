<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use PhpAiToolkit\Doctest\Execution\RunFailure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RunFailure::class)]
final class RunFailureTest extends TestCase
{
    public function testExposesTheFailedAssertion(): void
    {
        $failure = new RunFailure('add(1, 2)', 3, 'Values differ.', '3', '4');

        self::assertSame('add(1, 2)', $failure->code);
        self::assertSame(3, $failure->line);
        self::assertSame('Values differ.', $failure->message);
        self::assertSame('3', $failure->expected);
        self::assertSame('4', $failure->actual);
    }

    public function testLeavesTheComparedValuesOutWhenThereAreNone(): void
    {
        $failure = new RunFailure('boom()', 1, 'Statement raised RuntimeException: bad');

        self::assertNull($failure->expected);
        self::assertNull($failure->actual);
    }
}
