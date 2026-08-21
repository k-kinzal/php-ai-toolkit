<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Reporting;

use PhpAiToolkit\Doctest\Analysis\DocExample;
use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Analysis\Target;
use PhpAiToolkit\Doctest\Config\ReportConfig;
use PhpAiToolkit\Doctest\Execution\RunFailure;
use PhpAiToolkit\Doctest\Execution\RunResult;
use PhpAiToolkit\Doctest\Execution\SuiteResult;
use PhpAiToolkit\Doctest\Reporting\ResultSorter;
use PhpAiToolkit\Doctest\Reporting\TextReporter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextReporter::class)]
#[UsesClass(ResultSorter::class)]
#[UsesClass(SuiteResult::class)]
#[UsesClass(RunResult::class)]
#[UsesClass(RunFailure::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(DocExample::class)]
#[UsesClass(ReportConfig::class)]
final class TextReporterTest extends TestCase
{
    public function testReportSummarisesAPassingRun(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4, '', null, [], 'src/Ledger.php'), new DocExample(null, 'run()', 'tag', 0), 6);
        $result = new SuiteResult(2, [new RunResult($example, [])]);

        $report = (new TextReporter())->report($result, new ReportConfig('text', ['path', 'line']));

        self::assertSame(
            "Doctest passed. Every documented example holds.\nSummary: 2 files, 1 examples, 1 passed, 0 failed, 0 skipped.\n",
            $report,
        );
    }

    public function testReportListsTheFailingExamples(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4, 'App', null, [], 'src/Ledger.php'), new DocExample(null, 'run()', 'tag', 0), 6);
        $result = new SuiteResult(1, [new RunResult($example, [new RunFailure('run()', 1, 'Values differ.', '1', '2')])]);

        $report = (new TextReporter())->report($result, new ReportConfig('text', ['path', 'line']));

        self::assertStringStartsWith("Doctest found 1 failing examples.\n", $report);
        self::assertStringContainsString("src/Ledger.php:6 App\Ledger#1\n", $report);
        self::assertStringContainsString('line 1: Values differ.', $report);
    }
}
