<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Executor;

use function get_class;
use function in_array;
use function sprintf;

use Throwable;
use Toolkit\Doctest\Assertion\AssertionKind;
use Toolkit\Doctest\Assertion\AssertionParser;
use Toolkit\Doctest\Assertion\AssertionResult;
use Toolkit\Doctest\Assertion\ParsedExample;
use Toolkit\Doctest\Assertion\Statement;
use Toolkit\Doctest\Parser\Example;

/**
 * Executes doctest examples and verifies assertions.
 *
 * The executor handles parsing example code, executing statements,
 * and verifying assertions. It supports:
 * - Return value assertions (// =>)
 * - Output assertions (// Output:)
 * - Exception assertions (// throws)
 *
 * Whatever an example raises has to become a reported failure, because a
 * throwable escaping here would end the run at the first broken example instead
 * of reporting it alongside the others. Upstream catches Throwable at each call
 * site; here ExpressionEvaluator catches it where the code actually runs and
 * hands back an Evaluation, so the port has one boundary rather than three.
 *
 * @example Creating and checking executor
 *     $executor = new \Toolkit\Doctest\Executor\ExampleExecutor();
 *     $executor instanceof \Toolkit\Doctest\Executor\ExampleExecutor // => true
 */
final class ExampleExecutor
{
    /** @readonly */
    private AssertionParser $parser;

    /** @readonly */
    private ExpressionEvaluator $evaluator;

    /** @var list<string> */
    private array $loadedFiles = [];

    /**
     * Creates an executor, optionally loading a bootstrap file before the first example.
     *
     * @param string|null $bootstrap path to a bootstrap file to require before execution
     */
    public function __construct(
        /** @readonly */
        private ?string $bootstrap = null,
        ?AssertionParser $parser = null,
        ?ExpressionEvaluator $evaluator = null,
    ) {
        $this->parser = $parser ?? new AssertionParser();
        $this->evaluator = $evaluator ?? new ExpressionEvaluator();
    }

    /**
     * Executes an example and returns the result.
     *
     * Parses the example code, executes each statement, and verifies
     * any assertions.
     *
     * @param Example $example the example to execute
     * @return ExecutionResult the execution result
     */
    public function execute(Example $example): ExecutionResult
    {
        return $this->executeParsed($this->parser->parse($example));
    }

    /**
     * Executes a parsed example and returns the result.
     *
     * @param ParsedExample $parsed the parsed example to execute
     * @return ExecutionResult the execution result
     */
    public function executeParsed(ParsedExample $parsed): ExecutionResult
    {
        $this->loadSources($parsed->example->target->filePath);

        $context = new ExecutionContext();
        $failures = [];
        foreach ($parsed->statements as $statement) {
            $result = $this->executeStatement($statement, $context);
            if (!$result->passed) {
                $failures[] = $result;
            }
        }

        return new ExecutionResult($parsed->example, $failures === [], $failures);
    }

    /**
     * Requires the bootstrap file and the file the example documents, each once.
     */
    public function loadSources(string $targetFile): void
    {
        if ($this->bootstrap !== null && !in_array($this->bootstrap, $this->loadedFiles, true)) {
            $this->loadedFiles[] = $this->bootstrap;

            require_once $this->bootstrap;
        }

        if (!in_array($targetFile, $this->loadedFiles, true)) {
            $this->loadedFiles[] = $targetFile;

            require_once $targetFile;
        }
    }

    /**
     * Executes one statement and verifies whatever it asserts.
     */
    public function executeStatement(Statement $statement, ExecutionContext $context): AssertionResult
    {
        $assertion = $statement->assertion;
        if ($assertion !== null && $assertion->type === AssertionKind::EXCEPTION) {
            return $this->executeWithExpectedException($statement, $context);
        }

        $evaluation = $this->evaluator->evaluate($statement->code, $context);
        if (!$evaluation->completed()) {
            return $this->unexpectedException($statement, $evaluation->error);
        }

        if (!$statement->hasAssertion()) {
            return new AssertionResult(true, '', $statement);
        }

        return $this->checkAssertion($statement, $evaluation->value, $context);
    }

    /**
     * Executes a statement that is expected to throw, and checks what it threw.
     */
    public function executeWithExpectedException(Statement $statement, ExecutionContext $context): AssertionResult
    {
        $assertion = $statement->assertion;
        if ($assertion === null) {
            return new AssertionResult(true, '', $statement);
        }

        $evaluation = $this->evaluator->evaluate($statement->code, $context);
        $raised = $evaluation->error;
        if ($raised !== null) {
            return $this->checkThrownException($statement, $raised, $assertion->expectedRaw, $assertion->exceptionMessage);
        }

        return new AssertionResult(
            false,
            sprintf('Expected exception %s but none was thrown', $assertion->expectedRaw),
            $statement,
        );
    }

    /**
     * Reports a statement that raised where the example documented no exception.
     */
    public function unexpectedException(Statement $statement, ?Throwable $exception): AssertionResult
    {
        if ($exception === null) {
            return new AssertionResult(true, '', $statement);
        }

        return new AssertionResult(
            false,
            sprintf('Unexpected exception %s: %s', get_class($exception), $exception->getMessage()),
            $statement,
        );
    }

    /**
     * Checks a caught exception against the class and message the example documented.
     */
    public function checkThrownException(Statement $statement, Throwable $exception, string $expectedClass, ?string $expectedMessage): AssertionResult
    {
        if (!(new ExceptionMatcher())->matches($exception, $expectedClass)) {
            return new AssertionResult(
                false,
                sprintf('Expected exception %s but got %s: %s', $expectedClass, get_class($exception), $exception->getMessage()),
                $statement,
                $expectedClass,
                get_class($exception),
            );
        }

        if ($expectedMessage !== null && !str_contains($exception->getMessage(), $expectedMessage)) {
            return new AssertionResult(
                false,
                sprintf('Exception message "%s" does not contain "%s"', $exception->getMessage(), $expectedMessage),
                $statement,
                $expectedMessage,
                $exception->getMessage(),
            );
        }

        return new AssertionResult(true, '', $statement);
    }

    /**
     * Verifies the assertion of a statement that completed without throwing.
     *
     * @param mixed $actual the value the statement produced
     */
    public function checkAssertion(Statement $statement, $actual, ExecutionContext $context): AssertionResult
    {
        $assertion = $statement->assertion;
        if ($assertion === null || $assertion->type === AssertionKind::OUTPUT) {
            return $this->checkOutput($statement, $context->lastOutput);
        }

        return $this->checkReturnValue($statement, $actual);
    }

    /**
     * Checks the value of a statement against the expression the example documented.
     *
     * @param mixed $actual the value the statement produced
     */
    public function checkReturnValue(Statement $statement, $actual): AssertionResult
    {
        $assertion = $statement->assertion;
        if ($assertion === null) {
            return new AssertionResult(true, '', $statement);
        }

        $evaluation = $this->evaluator->evaluateExpected($assertion->expectedRaw);
        $raised = $evaluation->error;
        if ($raised !== null) {
            return new AssertionResult(
                false,
                sprintf('Failed to parse expected value: %s', $raised->getMessage()),
                $statement,
            );
        }

        $expected = $evaluation->value;
        if ($expected === $actual) {
            return new AssertionResult(true, '', $statement);
        }

        return new AssertionResult(false, 'Values do not match', $statement, $expected, $actual);
    }

    /**
     * Checks what a statement printed against the output the example documented.
     */
    public function checkOutput(Statement $statement, string $actualOutput): AssertionResult
    {
        $assertion = $statement->assertion;
        if ($assertion === null) {
            return new AssertionResult(true, '', $statement);
        }

        $expected = $assertion->expectedRaw;
        if ($expected === $actualOutput || $expected === rtrim($actualOutput)) {
            return new AssertionResult(true, '', $statement);
        }

        return new AssertionResult(false, 'Output does not match', $statement, $expected, $actualOutput);
    }
}
