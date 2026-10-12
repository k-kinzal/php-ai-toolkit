<?php

declare(strict_types=1);

namespace Toolkit\Rector;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Expression;
use Rector\Rector\AbstractRector;

/**
 * Rethrows an ignored exception; enabling this rule changes failure handling.
 */
final class ForbidEmptyCatchRector extends AbstractRector
{
    /**
     * @return list<class-string<Node>>
     */
    #[Override]
    public function getNodeTypes(): array
    {
        return [Catch_::class];
    }

    /**
     * Rethrows an existing catch variable without inventing a new binding.
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Catch_) {
            return null;
        }

        if ($node->stmts !== [] || $node->var === null) {
            return null;
        }

        $node->stmts = [new Expression(new Throw_(clone $node->var))];

        return $node;
    }
}
