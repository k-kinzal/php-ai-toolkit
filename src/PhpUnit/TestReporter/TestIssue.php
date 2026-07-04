<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

/**
 * Immutable value object representing a single test issue.
 *
 * Captures the type, location, message, and optional diff/source
 * information needed for both human and AI output formatting.
 */
final class TestIssue
{
    /**
     * Assertion failure (e.g. assertEquals mismatch).
     */
    public const TYPE_FAILED = 'failed';

    /**
     * Unexpected exception or error during test execution.
     */
    public const TYPE_ERROR = 'error';

    /**
     * Test considered risky (e.g. no assertions performed).
     */
    public const TYPE_RISKY = 'risky';

    /**
     * Test was skipped.
     */
    public const TYPE_SKIPPED = 'skipped';

    /**
     * @param self::TYPE_* $type issue category
     * @param string $testId fully qualified test identifier (e.g. Class::method)
     * @param string $testName short display name (e.g. ClassName::methodName)
     * @param string $testFile absolute path to the test file
     * @param int $testLine line number within the test file where the issue occurred
     * @param string $message error or failure message
     * @param string|null $diff comparison diff (expected vs actual) when available
     * @param string|null $sourceFile absolute path to the source file implicated by the stack trace
     * @param int|null $sourceLine line number within the source file
     */
    public function __construct(
        public readonly string $type,
        public readonly string $testId,
        public readonly string $testName,
        public readonly string $testFile,
        public readonly int $testLine,
        public readonly string $message,
        public readonly ?string $diff = null,
        public readonly ?string $sourceFile = null,
        public readonly ?int $sourceLine = null,
    ) {
    }
}
