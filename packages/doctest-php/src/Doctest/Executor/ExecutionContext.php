<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Executor;

use function array_key_exists;

/**
 * Maintains state across statement executions within an example.
 *
 * ExecutionContext tracks variables defined during example execution,
 * allowing statements to share state. It also captures output from
 * echo/print statements for output assertions.
 *
 * @example Checking variable existence
 *     $ctx = new \Toolkit\Doctest\Executor\ExecutionContext();
 *     $ctx->setVariable('x', 42);
 *     $ctx->hasVariable('x') // => true
 *
 * @example Getting variable value
 *     $ctx = new \Toolkit\Doctest\Executor\ExecutionContext();
 *     $ctx->setVariable('y', 99);
 *     $ctx->getVariable('y') // => 99
 *
 * @template T = mixed
 */
final class ExecutionContext
{
    /** @var list<string> */
    private const INTERNAL_VARIABLES = [
        '__doctest_result__',
        '__doctest_code__',
        '__doctest_context__',
        '__doctest_vars__',
        '__doctest_output__',
        'variables',
        'needsReturn',
        'evalCode',
    ];

    /** @var array<string, T> */
    private array $variables = [];

    /**
     * Output captured from the last statement execution.
     */
    public string $lastOutput = '';

    /**
     * Returns all variables defined in this context.
     *
     * @return array<string, T>
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * Sets multiple variables at once, filtering internal variables.
     *
     * @param array<string, T> $vars variables to set
     */
    public function setVariables(array $vars): void
    {
        foreach (self::INTERNAL_VARIABLES as $name) {
            unset($vars[$name]);
        }

        $this->variables = $vars;
    }

    /**
     * Sets a single variable in the context.
     *
     * @param T $value the value to store
     */
    public function setVariable(string $name, $value): void
    {
        $this->variables[$name] = $value;
    }

    /**
     * Gets a variable from the context.
     *
     * Returns null if the variable is not set.
     *
     * @return T|null the stored value, or null
     */
    public function getVariable(string $name)
    {
        return $this->variables[$name] ?? null;
    }

    /**
     * Checks if a variable exists in the context.
     */
    public function hasVariable(string $name): bool
    {
        return array_key_exists($name, $this->variables);
    }
}
