<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueAiFormatter;
use PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueFormatter;
use PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueGutter;
use PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueHumanFormatter;
use PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssuePathFormatter;
use PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueSummary;
use PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueTypePresentation;
use PhpAiToolkit\PhpUnit\TestReporter\TestFailureLineResolver;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueCollector;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueInput;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueSourceLocationResolver;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueSourceReader;
use PhpAiToolkit\PhpUnit\TestReporter\TestReporterRuntime;
use PhpAiToolkit\Shared\AgentDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function putenv;

/**
 * @covers \PhpAiToolkit\PhpUnit\TestReporter\TestReporterRuntime
 * @uses \PhpAiToolkit\Shared\AgentDetector
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\TestFailureLineResolver
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\TestIssue
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueAiFormatter
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\TestIssueCollector
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueFormatter
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueGutter
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueHumanFormatter
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\TestIssueInput
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssuePathFormatter
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\TestIssueSourceLocationResolver
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\TestIssueSourceReader
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueSummary
 * @uses \PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueTypePresentation
 */
#[CoversClass(TestReporterRuntime::class)]
#[UsesClass(AgentDetector::class)]
#[UsesClass(TestFailureLineResolver::class)]
#[UsesClass(TestIssue::class)]
#[UsesClass(TestIssueAiFormatter::class)]
#[UsesClass(TestIssueCollector::class)]
#[UsesClass(TestIssueFormatter::class)]
#[UsesClass(TestIssueGutter::class)]
#[UsesClass(TestIssueHumanFormatter::class)]
#[UsesClass(TestIssueInput::class)]
#[UsesClass(TestIssuePathFormatter::class)]
#[UsesClass(TestIssueSourceLocationResolver::class)]
#[UsesClass(TestIssueSourceReader::class)]
#[UsesClass(TestIssueSummary::class)]
#[UsesClass(TestIssueTypePresentation::class)]
final class TestReporterRuntimeTest extends TestCase
{
    public function testFromCurrentProcessCreatesRuntimeWithCollector(): void
    {
        $runtime = TestReporterRuntime::fromCurrentProcess(static function (): void {
        });

        self::assertSame([], $runtime->collector()->getIssues());
    }

    public function testIsAiModeReturnsTrueWhenAgentEnvironmentIsPresent(): void
    {
        putenv('CLAUDE_CODE=1');

        self::assertTrue(TestReporterRuntime::isAiMode());

        putenv('CLAUDE_CODE');
    }

    public function testCollectorReturnsSharedCollector(): void
    {
        $collector = new TestIssueCollector();
        $runtime = new TestReporterRuntime(
            $collector,
            new TestIssueFormatter(new AgentDetector(), '/'),
            static function (): void {
            },
            false,
        );

        self::assertSame($collector, $runtime->collector());
    }

    public function testWriteReportWritesSuccessWhenOutputWasReplaced(): void
    {
        $output = [];
        $runtime = new TestReporterRuntime(
            new TestIssueCollector(),
            new TestIssueFormatter(new AgentDetector(), '/'),
            static function (string $message) use (&$output): void {
                $output[] = $message;
            },
            true,
        );

        $runtime->writeReport();

        self::assertSame(["No test failures\n"], $output);
    }

    public function testWriteReportWritesFormattedIssues(): void
    {
        $output = [];
        $collector = new TestIssueCollector();
        $collector->record(new TestIssueInput(TestIssue::TYPE_FAILED, 'T::m', 'T::m', '/tmp/T.php', 1, 'Failed'));
        $runtime = new TestReporterRuntime(
            $collector,
            new TestIssueFormatter(new AgentDetector(), '/'),
            static function (string $message) use (&$output): void {
                $output[] = $message;
            },
            false,
        );

        $runtime->writeReport();

        self::assertCount(1, $output);
        self::assertStringContainsString('1 failure', $output[0]);
    }
}
