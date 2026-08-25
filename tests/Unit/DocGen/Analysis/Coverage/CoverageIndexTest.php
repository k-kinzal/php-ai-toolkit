<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Coverage;

use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageIndex;
use PhpAiToolkit\DocGen\Analysis\Coverage\MethodCoverage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Coverage\CoverageIndex
 * @uses \PhpAiToolkit\DocGen\Analysis\Coverage\MethodCoverage
 */
#[CoversClass(CoverageIndex::class)]
#[UsesClass(MethodCoverage::class)]
final class CoverageIndexTest extends TestCase
{
    public function testAddLineDeduplicatesTestsAcrossCalls(): void
    {
        $index = new CoverageIndex();
        $index->addLine('src/Foo.php', 5, ['Tests\B::testB', 'Tests\A::testA']);
        $index->addLine('src/Foo.php', 5, ['Tests\A::testA']);

        self::assertSame(['Tests\A::testA', 'Tests\B::testB'], $index->testsForRange('src/Foo.php', 5, 5));
    }

    public function testTestsForRangeFiltersLinesAndSortsTests(): void
    {
        $index = new CoverageIndex();
        $index->addLine('src/Foo.php', 1, ['Tests\Z::testZ']);
        $index->addLine('src/Foo.php', 5, ['Tests\B::testB']);
        $index->addLine('src/Foo.php', 9, ['Tests\A::testA']);
        $index->addLine('src/Bar.php', 5, ['Tests\X::testX']);

        self::assertSame(['Tests\A::testA', 'Tests\B::testB'], $index->testsForRange('src/Foo.php', 2, 9));
        self::assertSame([], $index->testsForRange('src/Foo.php', 10, 20));
        self::assertSame([], $index->testsForRange('src/Missing.php', 1, 100));
    }

    public function testAddMethodRegistersCoverageForLookup(): void
    {
        $coverage = new MethodCoverage(5, 5, 100.0);
        $index = new CoverageIndex();
        $index->addMethod('src/Foo.php', 10, $coverage);

        self::assertSame($coverage, $index->methodAt('src/Foo.php', 8, 12));
    }

    public function testMethodAtPicksTheEarliestMethodStartingInRange(): void
    {
        $index = new CoverageIndex();
        $index->addMethod('src/Foo.php', 20, new MethodCoverage(2, 1, 50.0));
        $index->addMethod('src/Foo.php', 12, new MethodCoverage(4, 4, 100.0));

        self::assertSame(100.0, $index->methodAt('src/Foo.php', 10, 25)?->percent);
        self::assertSame(100.0, $index->methodAt('src/Foo.php', 12, 12)?->percent);
        self::assertSame(50.0, $index->methodAt('src/Foo.php', 15, 25)?->percent);
        self::assertNull($index->methodAt('src/Foo.php', 1, 11));
        self::assertNull($index->methodAt('src/Missing.php', 1, 100));
    }

    public function testIsEmptyReportsWhetherAnyDataWasLoaded(): void
    {
        $empty = new CoverageIndex();
        $empty->addLine('src/Foo.php', 1, []);

        self::assertTrue($empty->isEmpty());

        $withLine = new CoverageIndex();
        $withLine->addLine('src/Foo.php', 1, ['Tests\A::testA']);

        self::assertFalse($withLine->isEmpty());

        $withMethod = new CoverageIndex();
        $withMethod->addMethod('src/Foo.php', 1, new MethodCoverage(1, 1, 100.0));

        self::assertFalse($withMethod->isEmpty());
    }
}
