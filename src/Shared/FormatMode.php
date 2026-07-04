<?php

declare(strict_types=1);

namespace PhpAiToolkit\Shared;

/**
 * Output format mode constants for the dual-mode error formatter.
 */
final class FormatMode
{
    /**
     * Automatically detect the format based on environment.
     */
    public const AUTO = 'auto';

    /**
     * AI-readable structured plain text output.
     */
    public const AI = 'ai';

    /**
     * Human-readable rich terminal output with code context.
     */
    public const HUMAN = 'human';
}
