<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

use Closure;
use ErrorException;

use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function restore_error_handler;
use function set_error_handler;

/**
 * Evaluates example source in a scope carried across statements.
 *
 * The source is evaluated inside a static closure whose only locals are the
 * carried variables, so what an example defines is kept and nothing of the
 * evaluator itself leaks into the next statement.
 */
final class ExpressionEvaluator
{
    /** @readonly */
    private ReturnPolicy $returnPolicy;

    /**
     * Creates an evaluator from the return-value policy.
     */
    public function __construct(?ReturnPolicy $returnPolicy = null)
    {
        $this->returnPolicy = $returnPolicy ?? new ReturnPolicy();
    }

    /**
     * Evaluates one statement and records what it defined and printed.
     *
     * @return mixed the value of the evaluated expression, or null for source that has none
     *
     * @throws ErrorException when the example raises a diagnostic
     * @throws \PhpAiToolkit\Doctest\DoctestException when no parser can be created
     */
    public function evaluate(string $preamble, string $code, ExecutionContext $context): mixed
    {
        $evaluated = $this->evaluateSource($preamble . $this->returnPolicy->source($code), $context->variables());
        $context->remember($evaluated['variables']);
        $context->capture($evaluated['output']);

        return $evaluated['value'];
    }

    /**
     * Evaluates already-prepared source with the given variables in scope.
     *
     * A diagnostic raised while the example runs becomes a thrown exception, so
     * an example that only works by emitting a warning is reported as a failing
     * example instead of printing loose text over the report.
     *
     * @param array<string, mixed> $variables
     * @return array{value: mixed, variables: array<string, mixed>, output: string}
     *
     * @throws ErrorException when the example raises a diagnostic
     */
    public function evaluateSource(string $source, array $variables): array
    {
        $diagnostics = new DiagnosticLog();
        ob_start();
        set_error_handler($diagnostics->handler());

        try {
            $evaluated = ($this->evaluator())($variables, $source);
            $output = ob_get_contents();
            if ($diagnostics->raised()) {
                throw new ErrorException($diagnostics->summary());
            }

            return [
                'value' => $evaluated['value'],
                'variables' => $evaluated['variables'],
                'output' => $output === false ? '' : $output,
            ];
        } finally {
            restore_error_handler();
            ob_end_clean();
        }
    }

    /**
     * Returns the closure that owns the evaluation scope.
     *
     * @return Closure(array<string, mixed>, string): array{value: mixed, variables: array<string, mixed>}
     */
    public function evaluator(): Closure
    {
        return static function (array $__doctest_variables, string $__doctest_source): array {
            extract($__doctest_variables, EXTR_SKIP);
            unset($__doctest_variables);
            $__doctest_value = eval($__doctest_source);
            $__doctest_defined = get_defined_vars();
            unset($__doctest_defined['__doctest_source'], $__doctest_defined['__doctest_value']);

            return ['value' => $__doctest_value, 'variables' => $__doctest_defined];
        };
    }
}
