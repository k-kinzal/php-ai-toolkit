<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Executor;

/**
 * Evaluates arbitrary doctest code at the public runtime boundary.
 */
interface ExpressionEvaluation
{
    /**
     * Evaluates one statement while carrying its variables and output forward.
     *
     * @return Evaluation<mixed>|Evaluation<null>
     */
    public function evaluate(string $code, ExecutionContext $context): Evaluation;

    /**
     * Evaluates the expression that supplies an assertion's expected value.
     *
     * @return Evaluation<mixed>|Evaluation<null>
     */
    public function evaluateExpected(string $expectedRaw): Evaluation;
}
