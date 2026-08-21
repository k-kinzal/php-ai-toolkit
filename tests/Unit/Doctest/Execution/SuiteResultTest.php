<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use PhpAiToolkit\Doctest\Analysis\DocExample;
use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Analysis\Target;
use PhpAiToolkit\Doctest\Execution\RunFailure;
use PhpAiToolkit\Doctest\Execution\RunResult;
use PhpAiToolkit\Doctest\Execution\SuiteResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SuiteResult::class)]
#[UsesClass(RunResult::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(DocExample::class)]
#[UsesClass(RunFailure::class)]
final class SuiteResultTest extends TestCase
{
    public function testTotalCountsEveryExampleFound(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4), new DocExample(null, 'run()', 'tag', 0), 6);
        $result = new SuiteResult(2, [new RunResult($example, []), new RunResult($example, [], true)]);

        self::assertSame(2, $result->total());
        self::assertSame(2, $result->fileCount);
        self::assertCount(2, $result->results);
    }

    public function testPassedCountExcludesFailedAndSkippedExamples(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4), new DocExample(null, 'run()', 'tag', 0), 6);
        $result = new SuiteResult(1, [
            new RunResult($example, []),
            new RunResult($example, [new RunFailure('run()', 1, 'nope')]),
            new RunResult($example, [], true),
        ]);

        self::assertSame(1, $result->passedCount());
    }

    public function testFailedCountCountsOnlyExamplesThatRanAndBroke(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4), new DocExample(null, 'run()', 'tag', 0), 6);
        $result = new SuiteResult(1, [new RunResult($example, [new RunFailure('run()', 1, 'nope')]), new RunResult($example, [])]);

        self::assertSame(1, $result->failedCount());
    }

    public function testSkippedCountCountsDisplayOnlyExamples(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4), new DocExample(null, 'run()', 'tag-inline', 0), 6);
        $result = new SuiteResult(1, [new RunResult($example, [], true), new RunResult($example, [])]);

        self::assertSame(1, $result->skippedCount());
    }

    public function testFailedReturnsTheBrokenResultsOnly(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4), new DocExample(null, 'run()', 'tag', 0), 6);
        $broken = new RunResult($example, [new RunFailure('run()', 1, 'nope')]);
        $result = new SuiteResult(1, [new RunResult($example, []), $broken, new RunResult($example, [], true)]);

        self::assertSame([$broken], $result->failed());
    }

    public function testHasFailuresReportsWhetherAnyExampleBroke(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4), new DocExample(null, 'run()', 'tag', 0), 6);

        self::assertFalse((new SuiteResult(1, [new RunResult($example, [])]))->hasFailures());
        self::assertTrue((new SuiteResult(1, [new RunResult($example, [new RunFailure('run()', 1, 'nope')])]))->hasFailures());
    }
}
