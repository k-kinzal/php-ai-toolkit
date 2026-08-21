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
use PhpAiToolkit\Doctest\Reporting\AiFailureFormatter;
use PhpAiToolkit\Doctest\Reporting\AiReporter;
use PhpAiToolkit\Doctest\Reporting\AiReportGuidance;
use PhpAiToolkit\Doctest\Reporting\AiReportSummary;
use PhpAiToolkit\Doctest\Reporting\ResultSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AiReporter::class)]
#[UsesClass(AiReportSummary::class)]
#[UsesClass(AiReportGuidance::class)]
#[UsesClass(AiFailureFormatter::class)]
#[UsesClass(ResultSorter::class)]
#[UsesClass(SuiteResult::class)]
#[UsesClass(RunResult::class)]
#[UsesClass(RunFailure::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(DocExample::class)]
#[UsesClass(ReportConfig::class)]
final class AiReporterTest extends TestCase
{
    public function testReportStatesPassedAndStopsAfterTheSummary(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4, '', null, [], 'src/Ledger.php'), new DocExample(null, 'run()', 'tag', 0), 6);
        $result = new SuiteResult(1, [new RunResult($example, [])]);

        $report = (new AiReporter())->report($result, new ReportConfig('ai', ['path', 'line']));

        self::assertSame("DOCTEST_PASSED\nsummary:\n- files: 1\n- examples: 1\n- passed: 1\n- failed: 0\n- skipped: 0\n", $report);
    }

    public function testReportStatesFailedAndListsEveryBrokenExample(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4, 'App', null, [], 'src/Ledger.php'), new DocExample(null, 'run()', 'tag', 0), 6);
        $result = new SuiteResult(1, [new RunResult($example, [new RunFailure('run()', 1, 'Values differ.', '1', '2')])]);

        $report = (new AiReporter())->report($result, new ReportConfig('ai', ['path', 'line']));

        self::assertStringStartsWith("DOCTEST_FAILED\n", $report);
        self::assertStringContainsString('guidance:', $report);
        self::assertStringContainsString('1. src/Ledger.php:6 [doctest]', $report);
        self::assertStringContainsString("rerun: vendor/bin/doctest --filter='App\Ledger#1'", $report);
    }
}
