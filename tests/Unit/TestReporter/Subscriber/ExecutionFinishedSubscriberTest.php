<?php

declare(strict_types=1);

namespace Tests\Unit\TestReporter\Subscriber;

use Override;
use PhpStanAiRules\Support\AgentDetector;
use PhpStanAiRules\TestReporter\Subscriber\ExecutionFinishedSubscriber;
use PhpStanAiRules\TestReporter\TestIssueCollector;
use PhpStanAiRules\TestReporter\TestIssueFormatter;
use PHPUnit\Event\Code\Throwable;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Support\PhpUnitEventFactory;

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
        $collector->recordFailure(new Failed(
            PhpUnitEventFactory::createTelemetryInfo(),
            PhpUnitEventFactory::createTestMethod('Tests\Unit\FooTest', 'testBar', '/foo.php', 1),
            new Throwable('Exception', 'fail', 'fail', '', null),
            null,
        ));

        $output = [];
        $formatter = new TestIssueFormatter(new AgentDetector(), '/');
        $subscriber = new ExecutionFinishedSubscriber($collector, $formatter, static function (string $msg) use (&$output): void {
            $output[] = $msg;
        });

        $subscriber->notify(new ExecutionFinished(PhpUnitEventFactory::createTelemetryInfo()));

        self::assertCount(1, $output);
        self::assertStringContainsString('1 failure', $output[0]);
    }

    public function testNotifyProducesNoOutputWhenNoIssuesAndNotReplaced(): void
    {
        $collector = new TestIssueCollector();
        $output = [];
        $formatter = new TestIssueFormatter(new AgentDetector(), '/');
        $subscriber = new ExecutionFinishedSubscriber($collector, $formatter, static function (string $msg) use (&$output): void {
            $output[] = $msg;
        }, false);

        $subscriber->notify(new ExecutionFinished(PhpUnitEventFactory::createTelemetryInfo()));

        self::assertSame([], $output);
    }

    public function testNotifyWritesSuccessMessageWhenReplacedAndNoIssues(): void
    {
        $collector = new TestIssueCollector();
        $output = [];
        $formatter = new TestIssueFormatter(new AgentDetector(), '/');
        $subscriber = new ExecutionFinishedSubscriber($collector, $formatter, static function (string $msg) use (&$output): void {
            $output[] = $msg;
        }, true);

        $subscriber->notify(new ExecutionFinished(PhpUnitEventFactory::createTelemetryInfo()));

        self::assertCount(1, $output);
        self::assertSame("No test failures\n", $output[0]);
    }
}
