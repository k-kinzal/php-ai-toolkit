<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Parallel;

use PhpAiToolkit\DocGen\Parallel\CpuCoreCounter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Parallel\CpuCoreCounter
 */
#[CoversClass(CpuCoreCounter::class)]
final class CpuCoreCounterTest extends TestCase
{
    public function testCountReportsAtLeastOneCoreAndRepeatsItsAnswer(): void
    {
        $counter = new CpuCoreCounter();

        self::assertGreaterThanOrEqual(1, $counter->count());
        self::assertSame($counter->count(), $counter->count());
    }

    public function testDetectFindsTheCoresOfTheMachineOrNothingAtAll(): void
    {
        $detected = (new CpuCoreCounter())->detect();

        self::assertTrue($detected === null || $detected >= 1);
    }

    public function testFromValueAcceptsOnlyAPlainPositiveNumber(): void
    {
        $counter = new CpuCoreCounter();

        self::assertSame(8, $counter->fromValue('8'));
        self::assertSame(4, $counter->fromValue("4\n"));
        self::assertNull($counter->fromValue('0'));
        self::assertNull($counter->fromValue('-2'));
        self::assertNull($counter->fromValue('2.5'));
        self::assertNull($counter->fromValue('many'));
        self::assertNull($counter->fromValue(''));
        self::assertNull($counter->fromValue(false));
        self::assertNull($counter->fromValue(null));
    }

    public function testFromCommandReadsTheNumberACommandPrintsOrNothing(): void
    {
        $counter = new CpuCoreCounter();

        self::assertSame(3, $counter->fromCommand('echo 3'));
        self::assertNull($counter->fromCommand('echo not-a-number'));
    }

    public function testFromProcCpuInfoCountsProcessorEntriesOfAReadableFile(): void
    {
        $path = sys_get_temp_dir() . '/docgen-cpuinfo-' . bin2hex(random_bytes(4));
        file_put_contents($path, "processor\t: 0\nmodel name\t: Demo\n\nprocessor\t: 1\nmodel name\t: Demo\n");
        $counter = new CpuCoreCounter();

        self::assertSame(2, $counter->fromProcCpuInfo($path));
        self::assertNull($counter->fromProcCpuInfo($path . '-missing'));

        unlink($path);
    }
}
