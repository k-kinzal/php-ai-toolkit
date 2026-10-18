<?php

declare(strict_types=1);

namespace DecisionsLint\Diagnostic;

/**
 * The three severities a rule can give its findings, ordered from error down to info.
 */
final class Severity
{
    /**
     * A violation the project treats as a defect.
     */
    public const ERROR = 'error';

    /**
     * A violation worth fixing that does not fail the check on its own.
     */
    public const WARNING = 'warning';

    /**
     * An observation that never fails the check.
     */
    public const INFO = 'info';

    /**
     * Every severity, strongest first.
     *
     * @var list<string>
     */
    public const ALL = [self::ERROR, self::WARNING, self::INFO];

    /**
     * Returns how strong a severity is: 3 for error, 2 for warning, 1 for info, 0 for anything else.
     */
    public function rank(string $severity): int
    {
        return match ($severity) {
            self::ERROR => 3,
            self::WARNING => 2,
            self::INFO => 1,
            default => 0,
        };
    }

    /**
     * Tells whether a severity is at least as strong as a minimum, such as warning against error.
     */
    public function atLeast(string $severity, string $minimum): bool
    {
        return $this->rank($minimum) > 0 && $this->rank($severity) >= $this->rank($minimum);
    }
}
