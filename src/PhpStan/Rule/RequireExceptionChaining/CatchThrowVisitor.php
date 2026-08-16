<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\RequireExceptionChaining;

use Override;
use PhpAiToolkit\PhpStan\Rule\Shared\ThrownExpression;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Collects the throw statements that belong directly to one catch block.
 *
 * Nested try/catch structures are skipped entirely — their catches are
 * evaluated independently when the rule visits them. Closures, arrow
 * functions, and anonymous classes are skipped because their throws run in
 * another scope.
 */
final class CatchThrowVisitor extends NodeVisitorAbstract
{
    /** @var list<Node> */
    private array $throws = [];

    /** @readonly */
    private ThrownExpression $thrown;

    /**
     * Creates the visitor from what tells a throw from any other node.
     */
    public function __construct(?ThrownExpression $thrown = null)
    {
        $this->thrown = $thrown ?? new ThrownExpression();
    }

    /**
     * Collects throw statements and skips nested scopes and try blocks.
     *
     * @return ?int a traversal instruction, or null to continue
     */
    #[Override]
    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Closure || $node instanceof ArrowFunction || $node instanceof Class_ || $node instanceof TryCatch) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($this->thrown->isThrow($node)) {
            $this->throws[] = $node;
        }

        return null;
    }

    /**
     * Returns the throw statements collected during traversal.
     *
     * @return list<Node>
     */
    public function throws(): array
    {
        return $this->throws;
    }
}
