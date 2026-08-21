<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Reporting;

use PhpAiToolkit\Doctest\Analysis\DocExample;
use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\Analysis\Target;
use PhpAiToolkit\Doctest\Execution\RunResult;
use PhpAiToolkit\Doctest\Execution\SuiteResult;
use PhpAiToolkit\Doctest\Reporting\AiReportSummary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AiReportSummary::class)]
#[UsesClass(SuiteResult::class)]
#[UsesClass(RunResult::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
#[UsesClass(DocExample::class)]
final class AiReportSummaryTest extends TestCase
{
    public function testSummaryCountsFilesExamplesAndOutcomes(): void
    {
        $example = new Example(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Ledger', 4), new DocExample(null, 'run()', 'tag', 0), 6);
        $result = new SuiteResult(3, [new RunResult($example, []), new RunResult($example, [], true)]);

        self::assertSame(
            "summary:\n- files: 3\n- examples: 2\n- passed: 1\n- failed: 0\n- skipped: 1\n",
            (new AiReportSummary())->summary($result),
        );
    }
}
