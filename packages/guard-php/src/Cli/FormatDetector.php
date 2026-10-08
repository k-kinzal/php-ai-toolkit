<?php

declare(strict_types=1);

namespace Guard\Cli;

/**
 * Detects agent sessions using the same markers as the toolkit reporters.
 */
final class FormatDetector
{
    /**
     * @var list<string>
     */
    private const MARKERS = [
        'AI_AGENT', 'CLAUDE_CODE', 'CLAUDECODE', 'CURSOR_TRACE_ID', 'CURSOR_AGENT',
        'GEMINI_CLI', 'CODEX_SANDBOX', 'CODEX_THREAD_ID', 'AUGMENT_AGENT', 'OPENCODE',
        'DEVIN', 'WINDSURF_SESSION_ID', 'AIDER', 'CLINE', 'CONTINUE_GLOBAL_DIR',
    ];

    /**
     * Returns a compact agent format or the human format for this invocation.
     */
    public function detect(): string
    {
        foreach (self::MARKERS as $marker) {
            $value = getenv($marker);
            if ($value !== false && ($marker !== 'AI_AGENT' || trim($value) !== '')) {
                return 'ai';
            }
        }
        return is_file('/opt/.devin') || is_dir('/opt/.devin') ? 'ai' : 'text';
    }
}
