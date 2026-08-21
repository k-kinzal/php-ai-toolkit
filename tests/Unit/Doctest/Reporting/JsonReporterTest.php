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
use PhpAiToolkit\Doctest\Reporting\JsonReporter;
use PhpAiToolkit\Doctest\Reporting\ResultSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonReporter::class)]
#[UsesClass(ResultSorter::class)]
#[UsesClass(SuiteResult::class)]
#[UsesClass(RunResult::class)]
#[UsesClass(RunFailure::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(DocExample::class)]
#[UsesClass(ReportConfig::class)]
final class JsonReporterTest extends TestCase
{
    public function testReportEncodesTheStatusSummaryAndFailures(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4, 'App', null, [], 'src/Ledger.php'), new DocExample(null, 'run()', 'tag', 0), 6);
        $result = new SuiteResult(1, [new RunResult($example, [new RunFailure('run()', 1, 'Values differ.', '1', '2')])]);

        $report = (new JsonReporter())->report($result, new ReportConfig('json', ['path', 'line']));
        $decoded = (array) json_decode($report, true);

        self::assertSame('failed', $decoded['status']);
        self::assertSame(['files' => 1, 'examples' => 1, 'passed' => 0, 'failed' => 1, 'skipped' => 0], $decoded['summary']);
        self::assertSame([['line' => 1, 'code' => 'run()', 'message' => 'Values differ.', 'expected' => '1', 'actual' => '2']], ((array) ((array) $decoded['failures'])[0])['assertions']);
        self::assertStringEndsWith("\n", $report);
    }

    public function testReportEncodesAPassingRunWithNoFailures(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4, '', null, [], 'src/Ledger.php'), new DocExample(null, 'run()', 'tag', 0), 6);
        $decoded = (array) json_decode((new JsonReporter())->report(new SuiteResult(1, [new RunResult($example, [])]), new ReportConfig('json', ['path'])), true);

        self::assertSame('passed', $decoded['status']);
        self::assertSame([], $decoded['failures']);
    }

    public function testFailureDescribesTheExampleAndItsAssertions(): void
    {
        $example = new Example(new Target(Target::METHOD, '/a.php', '/** */', 'append', 4, 'App', 'Ledger', [], 'src/Ledger.php'), new DocExample(null, 'run()', 'tag', 0), 6);

        $failure = (new JsonReporter())->failure(new RunResult($example, [new RunFailure('run()', 1, 'Values differ.', '1', '2')]));

        self::assertSame('App\Ledger::append()#1', $failure['id']);
        self::assertSame('src/Ledger.php', $failure['path']);
        self::assertSame(6, $failure['line']);
        self::assertSame('App\Ledger::append()', $failure['symbol']);
        self::assertSame([['line' => 1, 'code' => 'run()', 'message' => 'Values differ.', 'expected' => '1', 'actual' => '2']], $failure['assertions']);
    }
}
