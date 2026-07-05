<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueBlockIndenter;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueGutter;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueHumanFormatter;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssuePathFormatter;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueSourceReader;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueSummary;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueTypePresentation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestIssueHumanFormatter::class)]
final class TestIssueHumanFormatterTest extends TestCase
{
    public function testFormatGroupsIssuesByFile(): void
    {
        $formatter = new TestIssueHumanFormatter(
            new TestIssuePathFormatter('/project'),
            new TestIssueSourceReader(),
            new TestIssueTypePresentation(),
            new TestIssueSummary(),
            new TestIssueBlockIndenter(),
            new TestIssueGutter(),
        );
        $output = $formatter->format([
            new TestIssue(TestIssue::TYPE_FAILED, 'T::a', 'FooTest::testA', '/project/tests/FooTest.php', 10, 'Fail A'),
            new TestIssue(TestIssue::TYPE_ERROR, 'T::b', 'BarTest::testB', '/project/tests/BarTest.php', 20, 'Error B'),
        ]);

        self::assertStringContainsString('tests/FooTest.php', $output);
        self::assertStringContainsString('tests/BarTest.php', $output);
        self::assertStringContainsString('1 failure, 1 error', $output);
    }

    public function testFileBlockStartsWithRelativeFileHeader(): void
    {
        $formatter = new TestIssueHumanFormatter(
            new TestIssuePathFormatter('/project'),
            new TestIssueSourceReader(),
            new TestIssueTypePresentation(),
            new TestIssueSummary(),
            new TestIssueBlockIndenter(),
            new TestIssueGutter(),
        );

        self::assertStringStartsWith("\n <fg=cyan>tests/FooTest.php</>", $formatter->fileBlock('/project/tests/FooTest.php', [
            new TestIssue(TestIssue::TYPE_FAILED, 'T::a', 'FooTest::testA', '/project/tests/FooTest.php', 10, 'Fail A'),
        ]));
    }

    public function testSummaryLineUsesErrorStyleForFailures(): void
    {
        $formatter = new TestIssueHumanFormatter(
            new TestIssuePathFormatter('/project'),
            new TestIssueSourceReader(),
            new TestIssueTypePresentation(),
            new TestIssueSummary(),
            new TestIssueBlockIndenter(),
            new TestIssueGutter(),
        );

        self::assertSame(" <error> Found 1 failure in 1 test file </error>\n", $formatter->summaryLine([
            new TestIssue(TestIssue::TYPE_FAILED, 'T::a', 'FooTest::testA', '/project/tests/FooTest.php', 10, 'Fail A'),
        ], 1));
    }

    public function testIssueBlockIncludesTypeAndMessage(): void
    {
        $formatter = new TestIssueHumanFormatter(
            new TestIssuePathFormatter('/project'),
            new TestIssueSourceReader(),
            new TestIssueTypePresentation(),
            new TestIssueSummary(),
            new TestIssueBlockIndenter(),
            new TestIssueGutter(),
        );
        $output = $formatter->issueBlock(
            new TestIssue(TestIssue::TYPE_RISKY, 'T::a', 'FooTest::testA', '/project/tests/FooTest.php', 10, 'No assertions'),
            3,
        );

        self::assertStringContainsString('<fg=yellow>RISKY</>', $output);
        self::assertStringContainsString('No assertions', $output);
    }
}
