<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

/**
 * Immutable value object representing a single test issue.
 *
 * Captures the type, location, message, and optional diff/source
 * information needed for both human and AI output formatting.
 *
 * The properties are private and exposed read-only via __get, so the
 * value is immutable from the outside on PHP 8.0 (external writes raise
 * an Error because no __set is defined).
 *
 * @property-read self::TYPE_* $type issue category
 * @property-read string $testId fully qualified test identifier (e.g. Class::method)
 * @property-read string $testName short display name (e.g. ClassName::methodName)
 * @property-read string $testFile absolute path to the test file
 * @property-read int $testLine line number within the test file where the issue occurred
 * @property-read string $message error or failure message
 * @property-read string|null $diff comparison diff (expected vs actual) when available
 * @property-read string|null $sourceFile absolute path to the source file implicated by the stack trace
 * @property-read int|null $sourceLine line number within the source file
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
        /** @readonly */
        private string $type,
        /** @readonly */
        private string $testId,
        /** @readonly */
        private string $testName,
        /** @readonly */
        private string $testFile,
        /** @readonly */
        private int $testLine,
        /** @readonly */
        private string $message,
        /** @readonly */
        private ?string $diff = null,
        /** @readonly */
        private ?string $sourceFile = null,
        /** @readonly */
        private ?int $sourceLine = null,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return get_object_vars($this)[$name] ?? null;
    }
}
