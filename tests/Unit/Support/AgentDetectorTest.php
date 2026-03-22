<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Override;
use PhpStanAiRules\Support\AgentDetector;
use PhpStanAiRules\Support\FormatMode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function putenv;
use function sprintf;

#[CoversClass(AgentDetector::class)]
final class AgentDetectorTest extends TestCase
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

    public function testResolveModeDefaultsToHumanWhenNoEnvVars(): void
    {
        $detector = new AgentDetector();

        self::assertSame(FormatMode::HUMAN, $detector->resolveMode());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function providerAgentEnvVars(): array
    {
        return [
            'CLAUDE_CODE' => ['CLAUDE_CODE'],
            'CLAUDECODE' => ['CLAUDECODE'],
            'CURSOR_TRACE_ID' => ['CURSOR_TRACE_ID'],
            'CURSOR_AGENT' => ['CURSOR_AGENT'],
            'GEMINI_CLI' => ['GEMINI_CLI'],
            'CODEX_SANDBOX' => ['CODEX_SANDBOX'],
            'AUGMENT_AGENT' => ['AUGMENT_AGENT'],
            'OPENCODE' => ['OPENCODE'],
            'DEVIN' => ['DEVIN'],
            'WINDSURF_SESSION_ID' => ['WINDSURF_SESSION_ID'],
            'AIDER' => ['AIDER'],
            'CLINE' => ['CLINE'],
            'CONTINUE_GLOBAL_DIR' => ['CONTINUE_GLOBAL_DIR'],
        ];
    }

    #[DataProvider('providerAgentEnvVars')]
    public function testResolveModeDetectsAgentViaEnvVar(string $envVar): void
    {
        putenv(sprintf('%s=1', $envVar));

        $detector = new AgentDetector();

        self::assertSame(FormatMode::AI, $detector->resolveMode());
    }

    public function testResolveModeAiAgentEnvVarRequiresNonEmptyValue(): void
    {
        putenv('AI_AGENT=');

        $detector = new AgentDetector();

        self::assertSame(FormatMode::HUMAN, $detector->resolveMode());
    }

    public function testResolveModeAiAgentEnvVarWithNonEmptyValueDetectsAgent(): void
    {
        putenv('AI_AGENT=claude');

        $detector = new AgentDetector();

        self::assertSame(FormatMode::AI, $detector->resolveMode());
    }

    public function testIsRunningInAgentReturnsFalseByDefault(): void
    {
        $detector = new AgentDetector();

        self::assertFalse($detector->isRunningInAgent());
    }

    public function testIsRunningInAgentReturnsTrueWhenDetected(): void
    {
        putenv('CLAUDE_CODE=1');

        $detector = new AgentDetector();

        self::assertTrue($detector->isRunningInAgent());
    }
}
