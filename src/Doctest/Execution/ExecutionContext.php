<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

/**
 * Carries variables and captured output between the statements of one example.
 *
 * State is what lets an example read as a short program: a variable assigned
 * on one line is still there on the next.
 */
final class ExecutionContext
{
    /** @var array<string, mixed> */
    private array $variables = [];

    private string $output = '';

    /**
     * Returns every variable defined so far.
     *
     * @return array<string, mixed>
     */
    public function variables(): array
    {
        return $this->variables;
    }

    /**
     * Replaces the known variables with those a statement left behind.
     *
     * @param array<string, mixed> $variables
     */
    public function remember(array $variables): void
    {
        $this->variables = $variables;
    }

    /**
     * Returns the output the most recent statement produced.
     */
    public function output(): string
    {
        return $this->output;
    }

    /**
     * Records the output the most recent statement produced.
     */
    public function capture(string $output): void
    {
        $this->output = $output;
    }
}
