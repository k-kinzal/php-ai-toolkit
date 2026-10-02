<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Reporting;

use function sprintf;

use Toolkit\DocGuard\Analysis\Violation;

/**
 * Formats one violation block for AI DocGuard reports.
 *
 * The location carries a line number when the violation has one, and the
 * expected and actual lines are omitted when the violation has no heading
 * for them.
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
        $location = $violation->line === null ? $violation->path : sprintf('%s:%d', $violation->path, $violation->line);
        $output = sprintf("%d. %s [%s]\n", $number, $location, $violation->rule);
        if ($violation->expected !== null) {
            $output .= sprintf("   expected: %s\n", $violation->expected);
        }
        if ($violation->actual !== null) {
            $output .= sprintf("   actual: %s\n", $violation->actual);
        }

        return $output . sprintf("   message: %s\n   action: %s\n", $violation->message, $this->action->action($violation));
    }
}
