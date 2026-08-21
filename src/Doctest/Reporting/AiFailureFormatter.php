<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Reporting;

use PhpAiToolkit\Doctest\Execution\RunFailure;
use PhpAiToolkit\Doctest\Execution\RunResult;

use function sprintf;

/**
 * Formats one failing example block for AI doctest reports.
 *
 * @visibility namespace
 */
final class AiFailureFormatter
{
    /**
     * Returns one numbered failure block.
     */
    public function format(int $number, RunResult $result): string
    {
        $example = $result->example;
        $block = sprintf(
            "%d. %s:%d [doctest]\n   example: %s\n   rerun: vendor/bin/doctest --filter='%s'\n",
            $number,
            $example->target->reportPath(),
            $example->line,
            $example->id(),
            $example->id(),
        );

        foreach ($result->failures as $failure) {
            $block .= $this->assertion($failure);
        }

        return $block;
    }

    /**
     * Returns the block describing one failed assertion.
     */
    public function assertion(RunFailure $failure): string
    {
        $block = sprintf("   - line %d: %s\n     code: %s\n", $failure->line, $failure->message, $failure->code);
        if ($failure->expected !== null) {
            $block .= sprintf("     expected: %s\n", $failure->expected);
        }

        if ($failure->actual !== null) {
            $block .= sprintf("     actual: %s\n", $failure->actual);
        }

        return $block;
    }
}
