<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Reporting;

use function sprintf;

use Toolkit\LocGuard\Analysis\Violation;

/**
 * Formats one violation block for AI LocGuard reports.
 */
final class AiViolationFormatter
{
    /** @readonly */
    private AiViolationAction $action;

    /**
     * Creates a formatter from action selection.
     */
    public function __construct(?AiViolationAction $action = null)
    {
        $this->action = $action ?? new AiViolationAction();
    }

    /**
     * Returns one numbered violation block.
     */
    public function format(int $number, Violation $violation): string
    {
        return sprintf(
            "%d. %s:%d [%s]\n   policy: %s\n   actual: %d\n   limit: %d\n   message: %s\n   action: %s\n",
            $number,
            $violation->path,
            $violation->line,
            $violation->rule,
            $violation->policy,
            $violation->actual,
            $violation->limit,
            $violation->message,
            $this->action->action($violation),
        );
    }
}
