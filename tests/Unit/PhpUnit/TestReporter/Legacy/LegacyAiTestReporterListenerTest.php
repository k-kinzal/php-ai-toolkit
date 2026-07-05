<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Legacy;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\Legacy\LegacyAiTestReporterListener;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueCollector;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueFormatter;
use PhpAiToolkit\PhpUnit\TestReporter\TestReporterRuntime;
use PhpAiToolkit\Shared\AgentDetector;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use RuntimeException;

#[CoversNothing]
final class LegacyAiTestReporterListenerTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        if (!interface_exists('PHPUnit\Framework\TestListener')) {
            self::markTestSkipped('Requires PHPUnit 9 legacy TestListener API.');
        }
    }

    public function testAddErrorWritesSharedRuntimeReportAtRootSuiteEnd(): void
    {
        $output = [];
        $runtime = new TestReporterRuntime(
            new TestIssueCollector(),
            new TestIssueFormatter(new AgentDetector(), '/'),
            static function (string $message) use (&$output): void {
                $output[] = $message;
            },
            false,
        );
        $listener = new LegacyAiTestReporterListener($runtime);
        $suite = new TestSuite('root');

        $listener->startTestSuite($suite);
        $listener->addError(new self(__FUNCTION__), new RuntimeException('legacy error'), 0.0);
        $listener->endTestSuite($suite);

        self::assertCount(1, $output);
        self::assertStringContainsString('1 error', $output[0]);
        self::assertStringContainsString('legacy error', $output[0]);
    }

    public function testAddWarningLeavesSharedRuntimeReportEmpty(): void
    {
        $output = [];
        $runtime = new TestReporterRuntime(
            new TestIssueCollector(),
            new TestIssueFormatter(new AgentDetector(), '/'),
            static function (string $message) use (&$output): void {
                $output[] = $message;
            },
            false,
        );
        $listener = new LegacyAiTestReporterListener($runtime);
        $suite = new TestSuite('root');

        $listener->startTestSuite($suite);
        $listener->addWarning(new self(__FUNCTION__), new Warning('warning'), 0.0);
        $listener->endTestSuite($suite);

        self::assertSame([], $output);
    }

    public function testAddFailureWritesSharedRuntimeReportAtRootSuiteEnd(): void
    {
        $output = [];
        $runtime = new TestReporterRuntime(
            new TestIssueCollector(),
            new TestIssueFormatter(new AgentDetector(), '/'),
            static function (string $message) use (&$output): void {
                $output[] = $message;
            },
            false,
        );
        $listener = new LegacyAiTestReporterListener($runtime);
        $suite = new TestSuite('root');

        $listener->startTestSuite($suite);
        $listener->addFailure(new self(__FUNCTION__), new ExpectationFailedException('legacy failure'), 0.0);
        $listener->endTestSuite($suite);

        self::assertCount(1, $output);
        self::assertStringContainsString('1 failure', $output[0]);
        self::assertStringContainsString(self::class . '::' . __FUNCTION__, $output[0]);
    }

    public function testAddIncompleteTestLeavesSharedRuntimeReportEmpty(): void
    {
        $output = [];
        $runtime = new TestReporterRuntime(
            new TestIssueCollector(),
            new TestIssueFormatter(new AgentDetector(), '/'),
            static function (string $message) use (&$output): void {
                $output[] = $message;
            },
            false,
        );
        $listener = new LegacyAiTestReporterListener($runtime);
        $suite = new TestSuite('root');

        $listener->startTestSuite($suite);
        $listener->addIncompleteTest(new self(__FUNCTION__), new RuntimeException('incomplete'), 0.0);
        $listener->endTestSuite($suite);

        self::assertSame([], $output);
    }

    public function testAddRiskyTestWritesSharedRuntimeReportAtRootSuiteEnd(): void
    {
        $output = [];
        $runtime = new TestReporterRuntime(
            new TestIssueCollector(),
            new TestIssueFormatter(new AgentDetector(), '/'),
            static function (string $message) use (&$output): void {
                $output[] = $message;
            },
            false,
        );
        $listener = new LegacyAiTestReporterListener($runtime);
        $suite = new TestSuite('root');

        $listener->startTestSuite($suite);
        $listener->addRiskyTest(new self(__FUNCTION__), new RuntimeException('legacy risky'), 0.0);
        $listener->endTestSuite($suite);

        self::assertCount(1, $output);
        self::assertStringContainsString('1 risky', $output[0]);
    }

    public function testAddSkippedTestLeavesSharedRuntimeReportEmpty(): void
    {
        $output = [];
        $runtime = new TestReporterRuntime(
            new TestIssueCollector(),
            new TestIssueFormatter(new AgentDetector(), '/'),
            static function (string $message) use (&$output): void {
                $output[] = $message;
            },
            false,
        );
        $listener = new LegacyAiTestReporterListener($runtime);
        $suite = new TestSuite('root');

        $listener->startTestSuite($suite);
        $listener->addSkippedTest(new self(__FUNCTION__), new RuntimeException('skipped'), 0.0);
        $listener->endTestSuite($suite);

        self::assertSame([], $output);
    }

    public function testStartTestSuiteDefersOutputUntilRootSuiteEnds(): void
    {
        $output = [];
        $runtime = new TestReporterRuntime(
            new TestIssueCollector(),
            new TestIssueFormatter(new AgentDetector(), '/'),
            static function (string $message) use (&$output): void {
                $output[] = $message;
            },
            false,
        );
        $listener = new LegacyAiTestReporterListener($runtime);

        $listener->startTestSuite(new TestSuite('root'));
        $listener->startTestSuite(new TestSuite('nested'));
        $listener->addFailure(new self(__FUNCTION__), new ExpectationFailedException('legacy failure'), 0.0);
        $listener->endTestSuite(new TestSuite('nested'));

        self::assertSame([], $output);
    }

    public function testEndTestSuiteWritesOnceWhenRootSuiteEnds(): void
    {
        $output = [];
        $runtime = new TestReporterRuntime(
            new TestIssueCollector(),
            new TestIssueFormatter(new AgentDetector(), '/'),
            static function (string $message) use (&$output): void {
                $output[] = $message;
            },
            false,
        );
        $listener = new LegacyAiTestReporterListener($runtime);

        $listener->startTestSuite(new TestSuite('root'));
        $listener->startTestSuite(new TestSuite('nested'));
        $listener->addFailure(new self(__FUNCTION__), new ExpectationFailedException('legacy failure'), 0.0);
        $listener->endTestSuite(new TestSuite('nested'));
        $listener->endTestSuite(new TestSuite('root'));

        self::assertCount(1, $output);
    }

    public function testStartTestLeavesSharedRuntimeReportEmpty(): void
    {
        $output = [];
        $runtime = new TestReporterRuntime(
            new TestIssueCollector(),
            new TestIssueFormatter(new AgentDetector(), '/'),
            static function (string $message) use (&$output): void {
                $output[] = $message;
            },
            false,
        );
        $listener = new LegacyAiTestReporterListener($runtime);

        $listener->startTest(new self(__FUNCTION__));

        self::assertSame([], $output);
    }

    public function testEndTestLeavesSharedRuntimeReportEmpty(): void
    {
        $output = [];
        $runtime = new TestReporterRuntime(
            new TestIssueCollector(),
            new TestIssueFormatter(new AgentDetector(), '/'),
            static function (string $message) use (&$output): void {
                $output[] = $message;
            },
            false,
        );
        $listener = new LegacyAiTestReporterListener($runtime);

        $listener->endTest(new self(__FUNCTION__), 0.0);

        self::assertSame([], $output);
    }
}
