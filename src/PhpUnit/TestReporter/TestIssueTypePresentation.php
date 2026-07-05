<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

/**
 * Presents test issue types as labels and terminal colors.
 */
final class TestIssueTypePresentation
{
    /**
     * Returns the uppercase label for an issue type.
     *
     * @param TestIssue::TYPE_* $type issue type constant
     */
    public function label(string $type): string
    {
        return match ($type) {
            TestIssue::TYPE_FAILED => 'FAILED',
            TestIssue::TYPE_ERROR => 'ERROR',
            TestIssue::TYPE_RISKY => 'RISKY',
            TestIssue::TYPE_SKIPPED => 'SKIPPED',
        };
    }

    /**
     * Returns the Symfony Console color name for an issue type.
     *
     * @param TestIssue::TYPE_* $type issue type constant
     */
    public function color(string $type): string
    {
        return match ($type) {
            TestIssue::TYPE_FAILED, TestIssue::TYPE_ERROR => 'red',
            TestIssue::TYPE_RISKY => 'yellow',
            TestIssue::TYPE_SKIPPED => 'cyan',
        };
    }
}
