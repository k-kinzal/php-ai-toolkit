<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Analysis\Violation;
use Toolkit\TreeGuard\Reporting\ViolationFieldComparator;

/**
 * @covers \Toolkit\TreeGuard\Reporting\ViolationFieldComparator
 * @uses \Toolkit\TreeGuard\Analysis\Violation
 */
#[CoversClass(ViolationFieldComparator::class)]
#[UsesClass(Violation::class)]
final class ViolationFieldComparatorTest extends TestCase
{
    public function testCompareOrdersByConfiguredField(): void
    {
        $left = new Violation('src/A', 'max_files', 'src/*', 10, 5, 'A');
        $right = new Violation('src/B', 'empty_directory', 'src/**', 20, 10, 'B');

        self::assertLessThan(0, (new ViolationFieldComparator())->compare($left, $right, 'path'));
        self::assertGreaterThan(0, (new ViolationFieldComparator())->compare($left, $right, 'rule'));
        self::assertLessThan(0, (new ViolationFieldComparator())->compare($left, $right, 'actual'));
        self::assertLessThan(0, (new ViolationFieldComparator())->compare($left, $right, 'limit'));
    }

    public function testCompareOrdersNullCountsBeforeIntegers(): void
    {
        $counted = new Violation('src/A', 'max_files', 'src/*', 10, 5, 'A');
        $uncounted = new Violation('src/B', 'disallowed_file', 'src/**', null, null, 'B');

        self::assertGreaterThan(0, (new ViolationFieldComparator())->compare($counted, $uncounted, 'actual'));
        self::assertLessThan(0, (new ViolationFieldComparator())->compare($uncounted, $counted, 'limit'));
    }

    public function testCompareNullableIntOrdersNullFirst(): void
    {
        self::assertSame(0, (new ViolationFieldComparator())->compareNullableInt(null, null));
        self::assertSame(-1, (new ViolationFieldComparator())->compareNullableInt(null, 1));
        self::assertSame(1, (new ViolationFieldComparator())->compareNullableInt(1, null));
        self::assertSame(-1, (new ViolationFieldComparator())->compareNullableInt(1, 2));
        self::assertSame(0, (new ViolationFieldComparator())->compareNullableInt(2, 2));
    }
}
