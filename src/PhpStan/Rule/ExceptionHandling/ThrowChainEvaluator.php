<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ExceptionHandling;

use function is_string;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Variable;
use PhpParser\NodeFinder;
use Toolkit\PhpStan\Rule\Shared\ThrownExpression;

/**
 * Decides whether a throw inside a catch block chains the caught exception.
 */
final class ThrowChainEvaluator
{
    /** @readonly */
    private ThrownExpression $thrown;

    /**
     * Creates the evaluator from what reads the thrown expression.
     */
    public function __construct(?ThrownExpression $thrown = null)
    {
        $this->thrown = $thrown ?? new ThrownExpression();
    }

    /**
     * Reports whether the throw creates a new exception without referencing the caught one.
     *
     * Only instantiations and calls are evaluated; rethrowing a variable is
     * never a violation because the exception object itself is preserved.
     */
    public function violates(Node $throw, ?string $caughtVariableName): bool
    {
        $expr = $this->thrown->of($throw);
        if (!$expr instanceof CallLike) {
            return false;
        }

        if ($caughtVariableName === null) {
            return true;
        }

        $reference = (new NodeFinder())->findFirst(
            $expr,
            static fn (Node $node): bool => $node instanceof Variable
                && is_string($node->name)
                && $node->name === $caughtVariableName,
        );

        return $reference === null;
    }
}
