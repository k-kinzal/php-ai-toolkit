<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Subscriber;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\Subscriber\ExecutionFinishedSubscriber;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueCollector;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueFormatter;
use PhpAiToolkit\Shared\AgentDetector;
use PHPUnit\Event\Code\TestDox;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Code\Throwable;
use PHPUnit\Event\Telemetry\Duration;
use PHPUnit\Event\Telemetry\GarbageCollectorStatus;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Telemetry\Info;
use PHPUnit\Event\Telemetry\MemoryUsage;
use PHPUnit\Event\Telemetry\Snapshot;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Metadata\MetadataCollection;

use function putenv;

#[CoversClass(ExecutionFinishedSubscriber::class)]
final class ExecutionFinishedSubscriberTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        putenv('AI_AGENT');
        putenv('CLAUDE_CODE');
        putenv('CLAUDECODE');
        putenv('CURSOR_TRACE_ID');
        putenv('CURSOR_AGENT');
        putenv('GEMINI_CLI');
        putenv('CODEX_SANDBOX');
        putenv('AUGMENT_AGENT');
        putenv('OPENCODE');
        putenv('DEVIN');
        putenv('WINDSURF_SESSION_ID');
        putenv('AIDER');
        putenv('CLINE');
        putenv('CONTINUE_GLOBAL_DIR');
    }

    #[Override]
    protected function tearDown(): void
    {
        putenv('AI_AGENT');
        putenv('CLAUDE_CODE');
        putenv('CLAUDECODE');
        putenv('CURSOR_TRACE_ID');
        putenv('CURSOR_AGENT');
        putenv('GEMINI_CLI');
        putenv('CODEX_SANDBOX');
        putenv('AUGMENT_AGENT');
        putenv('OPENCODE');
        putenv('DEVIN');
        putenv('WINDSURF_SESSION_ID');
        putenv('AIDER');
        putenv('CLINE');
        putenv('CONTINUE_GLOBAL_DIR');
        parent::tearDown();
    }

    public function testNotifyWritesOutputWhenIssuesExist(): void
    {
        $collector = new TestIssueCollector();
        $time = HRTime::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        $gc = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $snapshot = new Snapshot($time, $memory, $memory, $gc);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $telemetryInfo = new Info($snapshot, $duration, $memory, $duration, $memory);
        $testMethod = new TestMethod(
            self::class,
            'testBar',
            '/foo.php',
            1,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );
        $collector->recordFailure(new Failed(
            $telemetryInfo,
            $testMethod,
            new Throwable('Exception', 'fail', 'fail', '', null),
            null,
        ));

        $output = [];
        $formatter = new TestIssueFormatter(new AgentDetector(), '/');
        $subscriber = new ExecutionFinishedSubscriber($collector, $formatter, static function (string $msg) use (&$output): void {
            $output[] = $msg;
        });

        $subscriber->notify(new ExecutionFinished($telemetryInfo));

        self::assertCount(1, $output);
        self::assertStringContainsString('1 failure', $output[0]);
    }

    public function testNotifyProducesNoOutputWhenNoIssuesAndNotReplaced(): void
    {
        $collector = new TestIssueCollector();
        $time = HRTime::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        $gc = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $snapshot = new Snapshot($time, $memory, $memory, $gc);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $telemetryInfo = new Info($snapshot, $duration, $memory, $duration, $memory);
        $output = [];
        $formatter = new TestIssueFormatter(new AgentDetector(), '/');
        $subscriber = new ExecutionFinishedSubscriber($collector, $formatter, static function (string $msg) use (&$output): void {
            $output[] = $msg;
        }, false);

        $subscriber->notify(new ExecutionFinished($telemetryInfo));

        self::assertSame([], $output);
    }

    public function testNotifyWritesSuccessMessageWhenReplacedAndNoIssues(): void
    {
        $collector = new TestIssueCollector();
        $time = HRTime::fromSecondsAndNanoseconds(0, 0);
        $memory = MemoryUsage::fromBytes(0);
        $gc = new GarbageCollectorStatus(0, 0, 0, 0, 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $snapshot = new Snapshot($time, $memory, $memory, $gc);
        $duration = Duration::fromSecondsAndNanoseconds(0, 0);
        $telemetryInfo = new Info($snapshot, $duration, $memory, $duration, $memory);
        $output = [];
        $formatter = new TestIssueFormatter(new AgentDetector(), '/');
        $subscriber = new ExecutionFinishedSubscriber($collector, $formatter, static function (string $msg) use (&$output): void {
            $output[] = $msg;
        }, true);

        $subscriber->notify(new ExecutionFinished($telemetryInfo));

        self::assertCount(1, $output);
        self::assertSame("No test failures\n", $output[0]);
    }
}
