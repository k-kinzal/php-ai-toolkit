<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Executor;

use function ob_end_clean;
use function ob_get_contents;
use function ob_start;

use ParseError;

use function preg_match;
use function sprintf;
use function str_ends_with;
use function trim;

/**
 * Evaluates example code in a scope carried across the statements of an example.
 *
 * Extracted from ExampleExecutor of k-kinzal/doctest-php, where the same work
 * lives in private evaluateCode() and codeNeedsReturn() methods; this package
 * forbids private methods and caps method length, so the evaluation is a
 * collaborator of its own. The behaviour is unchanged.
 *
 * @visibility parent
 */
final class ExpressionEvaluator
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
     * scope is handed on.
     *
     * @return mixed the value of the evaluated expression, or null for code that has none
     *
     * @throws ParseError when the example code is not valid PHP
     */
    public function evaluate(string $code, ExecutionContext $context)
    {
        $__doctest_vars__ = $context->getVariables();
        $__doctest_code__ = $this->codeNeedsReturn($code) ? 'return ' . $code . ';' : $code;

        ob_start();

        try {
            extract($__doctest_vars__, EXTR_SKIP);

            try {
                $__doctest_result__ = eval($__doctest_code__);
            } catch (ParseError $error) {
                throw $this->parseError($error, $__doctest_code__);
            }

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

            return $__doctest_result__;
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Evaluates the expression an assertion documents as its expected value.
     *
     * @return mixed the value the expression produced
     *
     * @throws ParseError when the documented value is not valid PHP
     */
    public function evaluateExpected(string $expectedRaw)
    {
        $source = 'return ' . $expectedRaw . ';';

        try {
            return eval($source);
        } catch (ParseError $error) {
            throw $this->parseError($error, $source);
        }
    }

    /**
     * Returns a syntax error that names the source it came from.
     *
     * A bare ParseError from eval() says what is wrong but not which text it
     * was reading, which is the part a reader of the failure needs.
     */
    public function parseError(ParseError $error, string $source): ParseError
    {
        return new ParseError(sprintf('%s in: %s', $error->getMessage(), $source), $error->getCode(), $error);
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
