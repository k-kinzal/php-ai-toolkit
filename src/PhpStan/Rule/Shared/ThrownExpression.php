<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Shared;

use function get_object_vars;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Throw_;

/**
 * Answers what one node throws, whichever parser produced it.
 *
 * A throw is two different nodes depending on the parser PHPStan runs
 * with: nikic/php-parser 5 always produces the throw expression, while
 * version 4 still produces a throw statement for a throw written as one.
 * The statement class is gone in version 5, so it is never named here;
 * the node is recognized by the type every version reports for it, and
 * the thrown expression is read from the public properties both shapes
 * carry.
 */
final class ThrownExpression
{
    /**
     * The node type every parser version reports for a throw statement.
     */
    public const STATEMENT_TYPE = 'Stmt_Throw';

    /**
     * Returns the expression one node throws, or null when it throws nothing.
     */
    public function of(Node $node): ?Expr
    {
        if ($node instanceof Throw_) {
            return $node->expr;
        }

        if ($node->getType() !== self::STATEMENT_TYPE) {
            return null;
        }

        $expr = get_object_vars($node)['expr'] ?? null;

        return $expr instanceof Expr ? $expr : null;
    }

    /**
     * Reports whether one node throws.
     */
    public function isThrow(Node $node): bool
    {
        return $this->of($node) !== null;
    }
}
