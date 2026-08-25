<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;

/**
 * @covers \Toolkit\DocGen\Analysis\Diff\DiffStatus
 */
#[CoversClass(DiffStatus::class)]
final class DiffStatusTest extends TestCase
{
    public function testCombineTreatsNothingAsUnchanged(): void
    {
        self::assertSame(DiffStatus::SAME, (new DiffStatus())->combine([]));
    }

    public function testCombineKeepsTheStateEveryPartAgreesOn(): void
    {
        $status = new DiffStatus();

        self::assertSame(DiffStatus::ADDED, $status->combine([DiffStatus::ADDED, DiffStatus::ADDED]));
        self::assertSame(DiffStatus::REMOVED, $status->combine([DiffStatus::REMOVED]));
        self::assertSame(DiffStatus::SAME, $status->combine([DiffStatus::SAME, DiffStatus::SAME, DiffStatus::SAME]));
        self::assertSame(DiffStatus::MODIFIED, $status->combine([DiffStatus::MODIFIED, DiffStatus::MODIFIED]));
    }

    public function testCombineReportsPartsThatDisagreeAsModified(): void
    {
        $status = new DiffStatus();

        self::assertSame(DiffStatus::MODIFIED, $status->combine([DiffStatus::SAME, DiffStatus::ADDED]));
        self::assertSame(DiffStatus::MODIFIED, $status->combine([DiffStatus::ADDED, DiffStatus::REMOVED]));
        self::assertSame(DiffStatus::MODIFIED, $status->combine([DiffStatus::SAME, DiffStatus::SAME, DiffStatus::REMOVED]));
    }
}
