<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Legacy;

use function array_merge;
use function class_implements;
use function getenv;

use Override;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\Warning;

use function putenv;

use RuntimeException;

use function substr_count;

use Tests\Fixture\TestReporter\PhpUnitFixtureProcess;
use Toolkit\PhpUnit\TestReporter\Legacy\LegacyAiTestReporterListener;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueFormatter;
use Toolkit\PhpUnit\TestReporter\TestIssueCollector;
use Toolkit\PhpUnit\TestReporter\TestReporterRuntime;
use Toolkit\Shared\AgentDetector;

/**
 * @coversNothing
 */
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

        $paraTestEnvironment = getenv('PARATEST');
        putenv('PARATEST');
        $listener->addError(new self(__FUNCTION__), new RuntimeException('legacy error'), 0.0);
        putenv($paraTestEnvironment === false ? 'PARATEST' : 'PARATEST=' . $paraTestEnvironment);
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

        $paraTestEnvironment = getenv('PARATEST');
        putenv('PARATEST');
        $listener->addFailure(new self(__FUNCTION__), new ExpectationFailedException('legacy failure'), 0.0);
        putenv($paraTestEnvironment === false ? 'PARATEST' : 'PARATEST=' . $paraTestEnvironment);
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

        $paraTestEnvironment = getenv('PARATEST');
        putenv('PARATEST');
        $listener->addRiskyTest(new self(__FUNCTION__), new RuntimeException('legacy risky'), 0.0);
        putenv($paraTestEnvironment === false ? 'PARATEST' : 'PARATEST=' . $paraTestEnvironment);
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

    public function testEndTestSuiteWritesReportAtRootSuiteEndThroughPhpUnitRunner(): void
    {
        $environment = getenv();
        unset($environment['PARATEST']);
        $environment = array_merge($environment, ['AI_AGENT' => '1']);

        $result = PhpUnitFixtureProcess::runListener($environment);

        self::assertNotSame(0, $result->exitCode());
        self::assertStringContainsString('--- PHPUnit: 1 failure, 1 error, 1 risky ---', $result->output());
        self::assertStringContainsString('Tests\Fixture\TestReporter\FailingTest::testFails', $result->output());
        self::assertStringContainsString('Tests\Fixture\TestReporter\FailingTest::testErrors', $result->output());
        self::assertStringContainsString('Tests\Fixture\TestReporter\FailingTest::testIsRisky', $result->output());
        self::assertStringContainsString('fixture error', $result->output());
    }

    public function testStartTestSuiteTracksDepthSoNestedSuitesWriteReportOnceThroughPhpUnitRunner(): void
    {
        $environment = getenv();
        unset($environment['PARATEST']);
        $environment = array_merge($environment, ['AI_AGENT' => '1']);

        $result = PhpUnitFixtureProcess::runListener($environment);

        self::assertNotSame(0, $result->exitCode());
        self::assertSame(1, substr_count($result->output(), '--- PHPUnit: 1 failure, 1 error, 1 risky ---'));
    }
}
