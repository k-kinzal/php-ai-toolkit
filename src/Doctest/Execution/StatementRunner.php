<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

use function get_class;
use function rtrim;
use function sprintf;

use Throwable;

/**
 * Runs one statement of an example and reports the assertion that failed.
 *
 * Example code is arbitrary program text, so this is a process boundary in the
 * same sense a worker is: anything it raises has to become a reported failure,
 * because a throwable that escaped here would end the whole run at the first
 * broken example instead of reporting it alongside the others.
 */
final class StatementRunner
{
    /** @readonly */
    private ExpressionEvaluator $evaluator;

    /** @readonly */
    private ExceptionMatcher $exceptionMatcher;

    /** @readonly */
    private ValueFormatter $valueFormatter;

    /**
     * Creates a statement runner from evaluation, exception matching, and value rendering.
     */
    public function __construct(
        ?ExpressionEvaluator $evaluator = null,
        ?ExceptionMatcher $exceptionMatcher = null,
        ?ValueFormatter $valueFormatter = null,
    ) {
        $this->evaluator = $evaluator ?? new ExpressionEvaluator();
        $this->exceptionMatcher = $exceptionMatcher ?? new ExceptionMatcher();
        $this->valueFormatter = $valueFormatter ?? new ValueFormatter();
    }

    /**
     * Runs one statement and returns the failure it produced, or null when it held.
     */
    public function run(Statement $statement, string $preamble, ExecutionContext $context): ?RunFailure
    {
        if ($statement->marker === 'throws') {
            return $this->runExpectingException($statement, $preamble, $context);
        }

        try {
            $value = $this->evaluator->evaluate($preamble, $statement->code, $context);
        } catch (Throwable $thrown) {
            return new RunFailure(
                $statement->code,
                $statement->line,
                sprintf('Statement raised %s: %s', get_class($thrown), $thrown->getMessage()),
            );
        }

        if ($statement->marker === 'return') {
            return $this->checkReturnValue($statement, $value, $preamble);
        }

        return $statement->marker === 'output' ? $this->checkOutput($statement, $context->output()) : null;
    }

    /**
     * Runs a statement whose assertion names the exception it must raise.
     */
    public function runExpectingException(Statement $statement, string $preamble, ExecutionContext $context): ?RunFailure
    {
        $expected = $statement->expected ?? '';

        try {
            $this->evaluator->evaluate($preamble, $statement->code, $context);
        } catch (Throwable $thrown) {
            return $this->checkThrown($statement, $thrown, $expected);
        }

        return new RunFailure(
            $statement->code,
            $statement->line,
            sprintf('Expected %s to be thrown, but the statement completed.', $expected),
            $expected,
            'no exception',
        );
    }

    /**
     * Checks a caught exception against the class and message the example named.
     */
    public function checkThrown(Statement $statement, Throwable $thrown, string $expected): ?RunFailure
    {
        if (!$this->exceptionMatcher->matches($thrown, $expected)) {
            return new RunFailure(
                $statement->code,
                $statement->line,
                sprintf('Expected %s to be thrown, but %s was thrown instead.', $expected, get_class($thrown)),
                $expected,
                sprintf('%s: %s', get_class($thrown), $thrown->getMessage()),
            );
        }

        if ($this->exceptionMatcher->matchesMessage($thrown, $statement->exceptionMessage)) {
            return null;
        }

        return new RunFailure(
            $statement->code,
            $statement->line,
            sprintf('Expected the %s message to contain the documented text.', get_class($thrown)),
            $statement->exceptionMessage,
            $thrown->getMessage(),
        );
    }

    /**
     * Checks the value of a statement against the expression the example documented.
     */
    public function checkReturnValue(Statement $statement, mixed $actual, string $preamble): ?RunFailure
    {
        $source = $preamble . 'return ' . ($statement->expected ?? 'null') . ';';

        try {
            $expected = $this->evaluator->evaluateSource($source, [])['value'];
        } catch (Throwable $thrown) {
            return new RunFailure(
                $statement->code,
                $statement->line,
                sprintf('The documented value "%s" could not be evaluated: %s', $statement->expected ?? '', $thrown->getMessage()),
            );
        }

        if ($expected === $actual) {
            return null;
        }

        return new RunFailure(
            $statement->code,
            $statement->line,
            'The statement did not produce the documented value.',
            $this->valueFormatter->format($expected),
            $this->valueFormatter->format($actual),
        );
    }

    /**
     * Checks what a statement printed against the output the example documented.
     */
    public function checkOutput(Statement $statement, string $actual): ?RunFailure
    {
        $expected = $statement->expected ?? '';
        if ($expected === $actual || $expected === rtrim($actual, "\n")) {
            return null;
        }

        return new RunFailure(
            $statement->code,
            $statement->line,
            'The statement did not print the documented output.',
            $this->valueFormatter->format($expected),
            $this->valueFormatter->format($actual),
        );
    }
}
