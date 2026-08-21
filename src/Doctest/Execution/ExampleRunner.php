<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

use PhpAiToolkit\Doctest\Analysis\Example;
use PhpAiToolkit\Doctest\DoctestException;

/**
 * Runs one example end to end and reports every assertion that failed.
 *
 * All statements of an example run even after one fails, so a single run
 * reports every broken assertion in the example rather than only the first.
 */
final class ExampleRunner
{
    /** @readonly */
    private StatementBuilder $statementBuilder;

    /** @readonly */
    private StatementRunner $statementRunner;

    /** @readonly */
    private SourceLoader $sourceLoader;

    /**
     * Creates a runner from statement splitting, statement running, and source loading.
     */
    public function __construct(
        ?StatementBuilder $statementBuilder = null,
        ?StatementRunner $statementRunner = null,
        ?SourceLoader $sourceLoader = null,
    ) {
        $this->statementBuilder = $statementBuilder ?? new StatementBuilder();
        $this->statementRunner = $statementRunner ?? new StatementRunner();
        $this->sourceLoader = $sourceLoader ?? new SourceLoader();
    }

    /**
     * Runs one example and returns what it produced.
     *
     * @param Example $example the example to run
     * @param string|null $bootstrap a file to include once before the first example
     *
     * @throws DoctestException when the file the example documents cannot be loaded
     */
    public function run(Example $example, ?string $bootstrap = null): RunResult
    {
        if (!$example->runnable()) {
            return new RunResult($example, [], true);
        }

        $this->sourceLoader->load($example->target, $bootstrap);

        $context = new ExecutionContext();
        $preamble = $example->target->preamble();
        $failures = [];
        foreach ($this->statementBuilder->build($example->code()) as $statement) {
            $failure = $this->statementRunner->run($statement, $preamble, $context);
            if ($failure !== null) {
                $failures[] = $failure;
            }
        }

        return new RunResult($example, $failures);
    }
}
