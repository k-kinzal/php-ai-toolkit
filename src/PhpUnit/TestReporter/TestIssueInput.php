<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\TestReporter;

/**
 * Normalized test issue data produced by PHPUnit-version-specific adapters.
 *
 * @property-read TestIssue::TYPE_* $type issue category
 * @property-read string $testId fully qualified test identifier
 * @property-read string $testName display name
 * @property-read string $testFile absolute path to the test file
 * @property-read int $testLine fallback line in the test file
 * @property-read string $message error or failure message
 * @property-read string|null $diff comparison diff when available
 * @property-read string $stackTrace stack trace in the adapter's native text format
 */
final class TestIssueInput
{
    /**
     * @param TestIssue::TYPE_* $type issue category
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
        private string $stackTrace = '',
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
