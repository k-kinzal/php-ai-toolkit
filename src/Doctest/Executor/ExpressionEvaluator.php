<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Executor;

use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function preg_match;
use function str_ends_with;

use Throwable;

use function trim;

/**
 * Evaluates example code in a scope carried across the statements of an example.
 *
 * Extracted from ExampleExecutor of k-kinzal/doctest-php, where the same work
 * lives in private evaluateCode() and codeNeedsReturn() methods; this package
 * forbids private methods and caps method length, so the evaluation is a
 * collaborator of its own.
 *
 * This is the file that runs untrusted text, and therefore the only file in the
 * port that catches Throwable. Upstream catches around each call site instead;
 * catching here keeps the boundary in one place and hands the caller the same
 * information as an Evaluation.
 *
 * @visibility parent
 */
final class ExpressionEvaluator implements ExpressionEvaluation
{
    /** @var list<string> */
    private const NO_RETURN_PATTERNS = [
        '/^\$\w+\s*=/',
        '/^echo\s/',
        '/^print\s/',
        '/^return\s/',
        '/^if\s*\(/',
        '/^for\s*\(/',
        '/^foreach\s*\(/',
        '/^while\s*\(/',
        '/^do\s*\{/',
        '/^switch\s*\(/',
        '/^try\s*\{/',
        '/^throw\s/',
        '/^class\s/',
        '/^function\s/',
        '/^new\s+\w+\s*;$/',
    ];

    /**
     * Evaluates one statement, carrying its variables and output into the context.
     *
     * The code is evaluated inline rather than through a helper, which is how
     * k-kinzal/doctest-php does it and what makes carrying state work at all:
     * variables an example defines land in this method's scope, so they are
     * read back from here and this method's own locals are unset before the
     * scope is handed on. A statement that raises leaves the context untouched.
     *
     * @return Evaluation<mixed>|Evaluation<null>
     */
    public function evaluate(string $code, ExecutionContext $context): Evaluation
    {
        $__doctest_vars__ = $context->getVariables();
        $__doctest_code__ = $this->codeNeedsReturn($code) ? 'return ' . $code . ';' : $code;

        ob_start();

        try {
            extract($__doctest_vars__, EXTR_SKIP);
            $__doctest_result__ = eval($__doctest_code__);
            $__doctest_all_vars__ = get_defined_vars();
            unset(
                $__doctest_all_vars__['__doctest_vars__'],
                $__doctest_all_vars__['__doctest_code__'],
                $__doctest_all_vars__['__doctest_result__'],
                $__doctest_all_vars__['__doctest_all_vars__'],
                $__doctest_all_vars__['code'],
                $__doctest_all_vars__['context'],
            );
            $context->setVariables($__doctest_all_vars__);
            $output = ob_get_contents();
            $context->lastOutput = $output === false ? '' : $output;

            return new Evaluation($__doctest_result__);
        } catch (Throwable $error) {
            return new Evaluation(error: $error);
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Evaluates the expression an assertion documents as its expected value.
     *
     * @return Evaluation<mixed>|Evaluation<null>
     */
    public function evaluateExpected(string $expectedRaw): Evaluation
    {
        try {
            return new Evaluation(eval('return ' . $expectedRaw . ';'));
        } catch (Throwable $error) {
            return new Evaluation(error: $error);
        }
    }

    /**
     * Reports whether the code should be evaluated for its value.
     *
     * Code with a side effect — an assignment, an echo, a declaration, a
     * control structure — has no value worth asserting on.
     */
    public function codeNeedsReturn(string $code): bool
    {
        $trimmed = trim($code);
        foreach (self::NO_RETURN_PATTERNS as $pattern) {
            if (preg_match($pattern, $trimmed) === 1) {
                return false;
            }
        }

        return !str_ends_with($trimmed, ';');
    }
}
