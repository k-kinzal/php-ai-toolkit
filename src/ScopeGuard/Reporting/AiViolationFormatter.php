<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Reporting;

use function sprintf;

use Toolkit\ScopeGuard\Analysis\Violation;

/**
 * Formats one violation block for AI ScopeGuard reports.
 *
 * @visibility namespace
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
            "%d. %s:%d [%s]\n   symbol: %s\n   message: %s\n   action: %s\n",
            $number,
            $violation->path,
            $violation->line,
            $violation->rule,
            $violation->symbol,
            $violation->message,
            $this->action->action($violation),
        );
    }
}
