<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function putenv;

use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueAiFormatter;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueFormatter;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueGutter;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueHumanFormatter;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssuePathFormatter;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueSummary;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueTypePresentation;
use Toolkit\PhpUnit\TestReporter\TestFailureLineResolver;
use Toolkit\PhpUnit\TestReporter\TestIssue;
use Toolkit\PhpUnit\TestReporter\TestIssueCollector;
use Toolkit\PhpUnit\TestReporter\TestIssueInput;
use Toolkit\PhpUnit\TestReporter\TestIssueSourceLocationResolver;
use Toolkit\PhpUnit\TestReporter\TestIssueSourceReader;
use Toolkit\PhpUnit\TestReporter\TestReporterRuntime;
use Toolkit\Shared\AgentDetector;

/**
 * @covers \Toolkit\PhpUnit\TestReporter\TestReporterRuntime
 * @uses \Toolkit\Shared\AgentDetector
 * @uses \Toolkit\PhpUnit\TestReporter\TestFailureLineResolver
 * @uses \Toolkit\PhpUnit\TestReporter\TestIssue
 * @uses \Toolkit\PhpUnit\TestReporter\Presentation\TestIssueAiFormatter
 * @uses \Toolkit\PhpUnit\TestReporter\TestIssueCollector
 * @uses \Toolkit\PhpUnit\TestReporter\Presentation\TestIssueFormatter
 * @uses \Toolkit\PhpUnit\TestReporter\Presentation\TestIssueGutter
 * @uses \Toolkit\PhpUnit\TestReporter\Presentation\TestIssueHumanFormatter
 * @uses \Toolkit\PhpUnit\TestReporter\TestIssueInput
 * @uses \Toolkit\PhpUnit\TestReporter\Presentation\TestIssuePathFormatter
 * @uses \Toolkit\PhpUnit\TestReporter\TestIssueSourceLocationResolver
 * @uses \Toolkit\PhpUnit\TestReporter\TestIssueSourceReader
 * @uses \Toolkit\PhpUnit\TestReporter\Presentation\TestIssueSummary
 * @uses \Toolkit\PhpUnit\TestReporter\Presentation\TestIssueTypePresentation
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
