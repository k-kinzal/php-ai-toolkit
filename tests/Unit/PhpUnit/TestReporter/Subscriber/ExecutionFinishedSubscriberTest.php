<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Subscriber;

use function interface_exists;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\Subscriber\ExecutionFinishedSubscriber;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueCollector;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueFormatter;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueInput;
use PhpAiToolkit\PhpUnit\TestReporter\TestReporterRuntime;
use PhpAiToolkit\Shared\AgentDetector;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function putenv;

use Tests\Fixture\PhpUnitInternalObjectFactory;

#[CoversClass(ExecutionFinishedSubscriber::class)]
#[CoversClass(TestReporterRuntime::class)]
final class ExecutionFinishedSubscriberTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        if (!interface_exists('PHPUnit\Runner\Extension\Extension')) {
            self::markTestSkipped('Requires PHPUnit 10 event extension API.');
        }

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
        $telemetryInfo = PhpUnitInternalObjectFactory::telemetryInfo();
        $collector->record(new TestIssueInput(TestIssue::TYPE_FAILED, self::class . '::testBar', self::class . '::testBar', '/foo.php', 1, 'fail'));

        $output = [];
        $formatter = new TestIssueFormatter(new AgentDetector(), '/');
        $runtime = new TestReporterRuntime($collector, $formatter, static function (string $msg) use (&$output): void {
            $output[] = $msg;
        }, false);
        $subscriber = new ExecutionFinishedSubscriber($runtime);

        $subscriber->notify(new ExecutionFinished($telemetryInfo));

        self::assertCount(1, $output);
        self::assertStringContainsString('1 failure', $output[0]);
    }

    public function testNotifyProducesNoOutputWhenNoIssuesAndNotReplaced(): void
    {
        $collector = new TestIssueCollector();
        $telemetryInfo = PhpUnitInternalObjectFactory::telemetryInfo();
        $output = [];
        $formatter = new TestIssueFormatter(new AgentDetector(), '/');
        $runtime = new TestReporterRuntime($collector, $formatter, static function (string $msg) use (&$output): void {
            $output[] = $msg;
        }, false);
        $subscriber = new ExecutionFinishedSubscriber($runtime);

        $subscriber->notify(new ExecutionFinished($telemetryInfo));

        self::assertSame([], $output);
    }

    public function testNotifyWritesSuccessMessageWhenReplacedAndNoIssues(): void
    {
        $collector = new TestIssueCollector();
        $telemetryInfo = PhpUnitInternalObjectFactory::telemetryInfo();
        $output = [];
        $formatter = new TestIssueFormatter(new AgentDetector(), '/');
        $runtime = new TestReporterRuntime($collector, $formatter, static function (string $msg) use (&$output): void {
            $output[] = $msg;
        }, true);
        $subscriber = new ExecutionFinishedSubscriber($runtime);

        $subscriber->notify(new ExecutionFinished($telemetryInfo));

        self::assertCount(1, $output);
        self::assertSame("No test failures\n", $output[0]);
    }
}
