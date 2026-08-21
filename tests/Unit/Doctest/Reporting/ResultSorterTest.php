<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Reporting;

use PhpAiToolkit\Doctest\Analysis\DocExample;
use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Analysis\Target;
use PhpAiToolkit\Doctest\Config\ReportConfig;
use PhpAiToolkit\Doctest\Execution\RunResult;
use PhpAiToolkit\Doctest\Reporting\ResultSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResultSorter::class)]
#[UsesClass(RunResult::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(DocExample::class)]
#[UsesClass(ReportConfig::class)]
final class ResultSorterTest extends TestCase
{
    public function testSortOrdersByTheConfiguredFields(): void
    {
        $first = new RunResult(new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'A', 1, '', null, [], 'src/A.php'), new DocExample(null, 'a()', 'tag', 0), 5), []);
        $second = new RunResult(new Example(new Target(Target::CLASS_LIKE, '/b.php', '/** */', 'B', 1, '', null, [], 'src/B.php'), new DocExample(null, 'b()', 'tag', 0), 2), []);

        $sorted = (new ResultSorter())->sort([$second, $first], new ReportConfig('ai', ['path']));

        self::assertSame([$first, $second], $sorted);
        self::assertSame([$second, $first], (new ResultSorter())->sort([$first, $second], new ReportConfig('ai', ['line'])));
        self::assertSame([$second, $first], (new ResultSorter())->sort([$second, $first], new ReportConfig('ai', [])));
    }

    public function testCompareRanksResultsOnOneField(): void
    {
        $sorter = new ResultSorter();
        $first = new RunResult(new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'A', 1, '', null, [], 'src/A.php'), new DocExample(null, 'a()', 'tag', 0), 5), []);
        $second = new RunResult(new Example(new Target(Target::CLASS_LIKE, '/b.php', '/** */', 'B', 1, '', null, [], 'src/B.php'), new DocExample(null, 'b()', 'tag', 0), 2), []);

        self::assertLessThan(0, $sorter->compare($first, $second, 'path'));
        self::assertGreaterThan(0, $sorter->compare($first, $second, 'line'));
        self::assertLessThan(0, $sorter->compare($first, $second, 'symbol'));
    }
}
