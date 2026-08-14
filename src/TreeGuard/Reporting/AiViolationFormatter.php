<?php

declare(strict_types=1);

namespace PhpAiToolkit\TreeGuard\Reporting;

use PhpAiToolkit\TreeGuard\Analysis\Violation;

use function sprintf;

/**
 * Formats one violation block for AI TreeGuard reports.
 *
 * The actual and limit lines are omitted when the violation carries no
 * count-based values.
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
        $output = sprintf("%d. %s [%s]\n   pattern: %s\n", $number, $violation->path, $violation->rule, $violation->pattern);
        if ($violation->actual !== null) {
            $output .= sprintf("   actual: %d\n", $violation->actual);
        }
        if ($violation->limit !== null) {
            $output .= sprintf("   limit: %d\n", $violation->limit);
        }

        return $output . sprintf("   message: %s\n   action: %s\n", $violation->message, $this->action->action($violation));
    }
}
