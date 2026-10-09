<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Parallel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Parallel\CpuCoreCounter;
use Toolkit\DocGen\Parallel\WorkerCount;

/**
 * @covers \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\CpuCoreCounter
 */
#[CoversClass(WorkerCount::class)]
#[UsesClass(CpuCoreCounter::class)]
final class WorkerCountTest extends TestCase
{
    public function testResolveTakesTheCountARunAskedForAndNeverGoesBelowOne(): void
    {
        $count = new WorkerCount();

        self::assertSame(4, $count->resolve(4));
        self::assertSame(1, $count->resolve(1));
        self::assertSame(1, $count->resolve(0));
        self::assertSame(1, $count->resolve(-3));
    }

    public function testResolveDerivesTheDefaultFromTheCoresOfTheMachine(): void
    {
        $cores = new CpuCoreCounter();
        $count = new WorkerCount($cores);

        self::assertSame($count->defaultCount($cores->count()), $count->resolve(null));
    }

    public function testDefaultCountLeavesOneCoreAndStaysUnderTheCap(): void
    {
        $count = new WorkerCount();

        self::assertSame(7, $count->defaultCount(8));
        self::assertSame(1, $count->defaultCount(1));
        self::assertSame(1, $count->defaultCount(0));
        self::assertSame(WorkerCount::MAXIMUM, $count->defaultCount(128));
    }
}
