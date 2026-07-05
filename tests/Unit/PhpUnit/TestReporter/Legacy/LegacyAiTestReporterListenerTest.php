<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Legacy;

use function array_merge;
use function class_implements;
use function dirname;
use function fclose;
use function getenv;

use Override;

use const PHP_BINARY;

use PhpAiToolkit\PhpUnit\TestReporter\Legacy\LegacyAiTestReporterListener;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueCollector;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueFormatter;
use PhpAiToolkit\PhpUnit\TestReporter\TestReporterRuntime;
use PhpAiToolkit\Shared\AgentDetector;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\Warning;

use function proc_close;
use function proc_open;

use RuntimeException;

use function stream_get_contents;

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

        $listener->addError(new self(__FUNCTION__), new RuntimeException('legacy error'), 0.0);
        $runtime->writeReport();

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

        $listener->addWarning(new self(__FUNCTION__), new Warning('warning'), 0.0);
        $runtime->writeReport();

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

        $listener->addFailure(new self(__FUNCTION__), new ExpectationFailedException('legacy failure'), 0.0);
        $runtime->writeReport();

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

        $listener->addIncompleteTest(new self(__FUNCTION__), new RuntimeException('incomplete'), 0.0);
        $runtime->writeReport();

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

        $listener->addRiskyTest(new self(__FUNCTION__), new RuntimeException('legacy risky'), 0.0);
        $runtime->writeReport();

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

        $listener->addSkippedTest(new self(__FUNCTION__), new RuntimeException('skipped'), 0.0);
        $runtime->writeReport();

        self::assertSame([], $output);
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

    public function testListenerImplementsPhpUnitTestListener(): void
    {
        $interfaces = class_implements(LegacyAiTestReporterListener::class);

        self::assertIsArray($interfaces);
        self::assertContains(TestListener::class, $interfaces);
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

    public function testListenerReportsPhpUnitCallbacksThroughPhpUnitRunner(): void
    {
        $environment = getenv();
        unset($environment['PARATEST']);
        $environment = array_merge($environment, ['AI_AGENT' => '1']);

        $pipes = [];
        $process = proc_open(
            [
                PHP_BINARY,
                'vendor/bin/phpunit',
                '--configuration',
                'tests/Fixture/TestReporter/phpunit-listener.xml.dist',
                '--colors=never',
            ],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            dirname(__DIR__, 5),
            $environment,
        );

        self::assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        self::assertIsString($stdout);
        self::assertIsString($stderr);
        self::assertNotSame(0, $exitCode);
        self::assertStringContainsString('--- PHPUnit: 1 failure, 1 error, 1 risky ---', $stdout . $stderr);
        self::assertStringContainsString('Tests\Fixture\TestReporter\FailingTest::testFails', $stdout . $stderr);
        self::assertStringContainsString('Tests\Fixture\TestReporter\FailingTest::testErrors', $stdout . $stderr);
        self::assertStringContainsString('Tests\Fixture\TestReporter\FailingTest::testIsRisky', $stdout . $stderr);
        self::assertStringContainsString('fixture error', $stdout . $stderr);
    }
}
