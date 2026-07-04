<?php

declare(strict_types=1);

namespace PhpAiToolkit\Shared;

/**
 * Detects whether an AI agent is driving the current session.
 *
 * Inspired by Vite's @vercel/detect-agent, this class checks environment
 * variables and filesystem markers to determine the output format mode.
 */
final class AgentDetector
{
    /** @var list<string> Environment variables indicating an AI agent. */
    private const AGENT_ENV_VARS = [
        'AI_AGENT',
        'CLAUDE_CODE',
        'CLAUDECODE',
        'CURSOR_TRACE_ID',
        'CURSOR_AGENT',
        'GEMINI_CLI',
        'CODEX_SANDBOX',
        'AUGMENT_AGENT',
        'OPENCODE',
        'DEVIN',
        'WINDSURF_SESSION_ID',
        'AIDER',
        'CLINE',
        'CONTINUE_GLOBAL_DIR',
    ];

    /** @var list<string> Filesystem markers indicating an AI agent environment. */
    private const AGENT_FS_MARKERS = [
        '/opt/.devin',
    ];

    /**
     * Resolves the output format mode via auto-detection.
     *
     * Checks agent env vars and filesystem markers. Returns AI mode
     * when an agent is detected, human mode otherwise.
     */
    public function resolveMode(): string
    {
        return $this->isRunningInAgent() ? FormatMode::AI : FormatMode::HUMAN;
    }

    /**
     * Checks whether an AI agent environment is detected.
     */
    public function isRunningInAgent(): bool
    {
        foreach (self::AGENT_ENV_VARS as $envVar) {
            $value = getenv($envVar);
            if ($value === false) {
                continue;
            }
            if ($envVar === 'AI_AGENT' && trim($value) === '') {
                continue;
            }

            return true;
        }

        foreach (self::AGENT_FS_MARKERS as $marker) {
            if (@file_exists($marker)) {
                return true;
            }
        }

        return false;
    }
}
