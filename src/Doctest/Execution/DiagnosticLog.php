<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

use Closure;

use function error_reporting;
use function implode;

/**
 * Collects the diagnostics an example raises while it runs.
 *
 * Warnings and notices are collected rather than printed, so a report stays
 * readable, and they are collected rather than thrown at once, so an example
 * that raises several is reported with all of them.
 */
final class DiagnosticLog
{
    /** @var list<string> */
    private array $messages = [];

    /** @readonly */
    private int $ambientLevel;

    /**
     * Creates a log against the error level in force around the example.
     *
     * @param int|null $ambientLevel the reporting level to compare against, current level by default
     */
    public function __construct(?int $ambientLevel = null)
    {
        $this->ambientLevel = $ambientLevel ?? error_reporting();
    }

    /**
     * Returns the error handler that feeds this log.
     *
     * @return Closure(int, string): bool
     */
    public function handler(): Closure
    {
        return function (int $severity, string $message): bool {
            return $this->record($severity, $message);
        };
    }

    /**
     * Records one diagnostic unless the current error level suppresses it.
     *
     * @return bool true when the diagnostic was handled here, false to let PHP report it
     */
    public function record(int $severity, string $message): bool
    {
        if ($this->suppressed($severity, error_reporting())) {
            return false;
        }

        $this->messages[] = $message;

        return true;
    }

    /**
     * Reports whether the diagnostic was silenced by the at-operator.
     *
     * The reporting level is compared against the one in force when the log was
     * created rather than against a fixed mask, because a test runner narrows
     * the reporting level while its own handler is installed, and that
     * narrowing must not be read as the example silencing anything.
     *
     * @param int $severity the level of the diagnostic that was raised
     * @param int $level the reporting level in force where it was raised
     */
    public function suppressed(int $severity, int $level): bool
    {
        return $level !== $this->ambientLevel && ($level & $severity) === 0;
    }

    /**
     * Reports whether any diagnostic was recorded.
     */
    public function raised(): bool
    {
        return $this->messages !== [];
    }

    /**
     * Returns every recorded diagnostic as one message.
     */
    public function summary(): string
    {
        return implode('; ', $this->messages);
    }
}
