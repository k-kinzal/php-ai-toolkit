<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use function dirname;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssue;
use PhpAiToolkit\PhpUnit\TestReporter\TestIssueFormatter;
use PhpAiToolkit\Shared\AgentDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function putenv;

#[CoversClass(TestIssueFormatter::class)]
final class TestIssueFormatterTest extends TestCase
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

    public function testFormatReturnsEmptyStringForNoIssues(): void
    {
        $formatter = new TestIssueFormatter(new AgentDetector(), '/project');

        self::assertSame('', $formatter->format([]));
    }

    public function testFormatAiModeSummaryFirst(): void
    {
        putenv('CLAUDE_CODE=1');

        $formatter = new TestIssueFormatter(new AgentDetector(), '/project');
        $output = $formatter->format([
            new TestIssue(TestIssue::TYPE_FAILED, 'T::m', 'T::m', '/project/tests/FooTest.php', 10, 'Fail'),
            new TestIssue(TestIssue::TYPE_ERROR, 'T::n', 'T::n', '/project/tests/BarTest.php', 20, 'Error'),
        ]);

        self::assertStringStartsWith('--- PHPUnit: 1 failure, 1 error ---', $output);
    }

    public function testFormatAiModePathLineFormat(): void
    {
        putenv('CLAUDE_CODE=1');

        $formatter = new TestIssueFormatter(new AgentDetector(), '/project');
        $output = $formatter->format([
            new TestIssue(TestIssue::TYPE_FAILED, 'T::m', 'FooTest::testBar', '/project/tests/FooTest.php', 42, 'Some failure'),
        ]);

        self::assertStringContainsString('tests/FooTest.php:42 [FAILED]', $output);
        self::assertStringContainsString('FooTest::testBar', $output);
        self::assertStringContainsString('Some failure', $output);
    }

    public function testFormatAiModeIncludesCodeContext(): void
    {
        putenv('CLAUDE_CODE=1');

        $fixtureFile = dirname(__DIR__, 3) . '/Fixture/TestReporter/SampleTest.php';
        $formatter = new TestIssueFormatter(new AgentDetector(), dirname(__DIR__, 4));
        $output = $formatter->format([
            new TestIssue(TestIssue::TYPE_FAILED, 'T::m', 'SampleTest::testGetName', $fixtureFile, 11, 'Assertion failed'),
        ]);

        self::assertStringContainsString("> self::assertSame('John', \$this->service->getName());", $output);
    }

    public function testFormatAiModeIncludesDiff(): void
    {
        putenv('CLAUDE_CODE=1');

        $formatter = new TestIssueFormatter(new AgentDetector(), '/project');
        $output = $formatter->format([
            new TestIssue(
                TestIssue::TYPE_FAILED,
                'T::m',
                'FooTest::testBar',
                '/project/tests/FooTest.php',
                42,
                'Failed asserting that two strings are identical.',
                "--- Expected\n+++ Actual\n-'John'\n+'Jane'",
            ),
        ]);

        self::assertStringContainsString('--- Expected', $output);
        self::assertStringContainsString('+++ Actual', $output);
        self::assertStringContainsString("-'John'", $output);
        self::assertStringContainsString("+'Jane'", $output);
    }

    public function testFormatAiModeIncludesSourceLocation(): void
    {
        putenv('CLAUDE_CODE=1');

        $formatter = new TestIssueFormatter(new AgentDetector(), '/project');
        $output = $formatter->format([
            new TestIssue(
                TestIssue::TYPE_ERROR,
                'T::m',
                'FooTest::testBar',
                '/project/tests/FooTest.php',
                42,
                'TypeError',
                null,
                '/project/src/Service/UserService.php',
                28,
            ),
        ]);

        self::assertStringContainsString('Source: src/Service/UserService.php:28', $output);
    }

    public function testFormatAiModeOmitsSourceWhenNull(): void
    {
        putenv('CLAUDE_CODE=1');

        $formatter = new TestIssueFormatter(new AgentDetector(), '/project');
        $output = $formatter->format([
            new TestIssue(TestIssue::TYPE_RISKY, 'T::m', 'T::m', '/project/tests/FooTest.php', 10, 'No assertions'),
        ]);

        self::assertStringNotContainsString('Source:', $output);
    }

    public function testFormatAiModeNoAnsiCodes(): void
    {
        putenv('CLAUDE_CODE=1');

        $formatter = new TestIssueFormatter(new AgentDetector(), '/project');
        $output = $formatter->format([
            new TestIssue(TestIssue::TYPE_FAILED, 'T::m', 'T::m', '/project/tests/FooTest.php', 10, 'Fail'),
        ]);

        self::assertStringNotContainsString('<fg=', $output);
        self::assertStringNotContainsString('</>', $output);
    }

    public function testFormatHumanModeGroupsByFile(): void
    {
        $formatter = new TestIssueFormatter(new AgentDetector(), '/project');
        $output = $formatter->format([
            new TestIssue(TestIssue::TYPE_FAILED, 'T::a', 'FooTest::testA', '/project/tests/FooTest.php', 10, 'Fail A'),
            new TestIssue(TestIssue::TYPE_FAILED, 'T::b', 'FooTest::testB', '/project/tests/FooTest.php', 20, 'Fail B'),
            new TestIssue(TestIssue::TYPE_ERROR, 'T::c', 'BarTest::testC', '/project/tests/BarTest.php', 30, 'Error C'),
        ]);

        self::assertStringContainsString('tests/FooTest.php', $output);
        self::assertStringContainsString('tests/BarTest.php', $output);
        self::assertStringContainsString('2 failures, 1 error', $output);
        self::assertStringContainsString('2 test files', $output);
    }

    public function testFormatHumanModeIncludesCarets(): void
    {
        $fixtureFile = dirname(__DIR__, 3) . '/Fixture/TestReporter/SampleTest.php';
        $formatter = new TestIssueFormatter(new AgentDetector(), dirname(__DIR__, 4));
        $output = $formatter->format([
            new TestIssue(TestIssue::TYPE_FAILED, 'T::m', 'SampleTest::testGetName', $fixtureFile, 11, 'Fail'),
        ]);

        self::assertStringContainsString('^^^^', $output);
        self::assertStringContainsString('assertSame', $output);
    }

    public function testFormatHumanModeUsesColorTags(): void
    {
        $formatter = new TestIssueFormatter(new AgentDetector(), '/project');
        $output = $formatter->format([
            new TestIssue(TestIssue::TYPE_FAILED, 'T::m', 'T::m', '/project/tests/FooTest.php', 10, 'Fail'),
        ]);

        self::assertStringContainsString('<fg=red>FAILED</>', $output);
        self::assertStringContainsString('<fg=cyan>', $output);
    }

    public function testFormatHumanModeIncludesSourceLocation(): void
    {
        $formatter = new TestIssueFormatter(new AgentDetector(), '/project');
        $output = $formatter->format([
            new TestIssue(
                TestIssue::TYPE_ERROR,
                'T::m',
                'T::m',
                '/project/tests/FooTest.php',
                42,
                'Error',
                null,
                '/project/src/Foo.php',
                28,
            ),
        ]);

        self::assertStringContainsString('Source:', $output);
        self::assertStringContainsString('src/Foo.php:28', $output);
    }

    public function testFormatHumanModeRiskyUsesYellowColor(): void
    {
        $formatter = new TestIssueFormatter(new AgentDetector(), '/project');
        $output = $formatter->format([
            new TestIssue(TestIssue::TYPE_RISKY, 'T::m', 'T::m', '/project/tests/FooTest.php', 10, 'No assertions'),
        ]);

        self::assertStringContainsString('<fg=yellow>RISKY</>', $output);
    }

    public function testFormatUsesRelativePaths(): void
    {
        putenv('CLAUDE_CODE=1');

        $formatter = new TestIssueFormatter(new AgentDetector(), '/home/user/project');
        $output = $formatter->format([
            new TestIssue(TestIssue::TYPE_FAILED, 'T::m', 'T::m', '/home/user/project/tests/FooTest.php', 10, 'Fail'),
        ]);

        self::assertStringContainsString('tests/FooTest.php:10', $output);
        self::assertStringNotContainsString('/home/user/project/', $output);
    }
}
