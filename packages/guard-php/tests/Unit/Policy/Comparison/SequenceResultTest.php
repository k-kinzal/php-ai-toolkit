<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Comparison;

use Guard\Policy\Comparison\SequenceResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Comparison\SequenceResult
 */
#[CoversClass(SequenceResult::class)]
final class SequenceResultTest extends TestCase
{
    public function testGetExposesUnexpectedItemsAndMissingSlots(): void
    {
        $result = new SequenceResult([[2, [0, 1]]], [3]);

        self::assertSame([[2, [0, 1]]], $result->unexpected);
        self::assertSame([3], $result->missing);
    }

    public function testCountAddsBothKindsOfFinding(): void
    {
        self::assertSame(3, (new SequenceResult([[0, []], [1, []]], [2]))->count());
        self::assertSame(0, (new SequenceResult([], []))->count());
    }
}
