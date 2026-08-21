<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Execution;

use function count;

use PhpAiToolkit\Doctest\DoctestException;

/**
 * Decides whether example source has to be evaluated for its value.
 *
 * The decision is made by parsing rather than by matching prefixes: source that
 * is exactly one expression statement has a value worth asserting on, and
 * anything else — an echo, a loop, a declaration — does not.
 */
final class ReturnPolicy
{
    /** @readonly */
    private SourceSyntax $syntax;

    /**
     * Creates the policy from the example source syntax check.
     */
    public function __construct(?SourceSyntax $syntax = null)
    {
        $this->syntax = $syntax ?? new SourceSyntax();
    }

    /**
     * Reports whether the source should be evaluated as a returned expression.
     *
     * @throws DoctestException when no parser can be created
     */
    public function needsReturn(string $code): bool
    {
        $statements = $this->syntax->parse($code);

        return $statements !== null && count($statements) === 1 && $statements[0] instanceof \PhpParser\Node\Stmt\Expression;
    }

    /**
     * Returns the source to evaluate, wrapped in a return when it has a value.
     *
     * @throws DoctestException when no parser can be created
     */
    public function source(string $code): string
    {
        return $this->needsReturn($code) ? 'return ' . $code . ';' : $code;
    }
}
