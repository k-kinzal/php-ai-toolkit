<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueCollector;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueFormatter;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueInput;
use PhpAiToolkit\PhpUnit\TestReporter\TestReporterRuntime;
use PhpAiToolkit\Shared\AgentDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function putenv;

#[CoversClass(TestReporterRuntime::class)]
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
