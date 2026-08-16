<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\RequireExceptionChaining;

use PhpParser\NodeTraverser;

/**
 * Collects the throw statements directly inside one catch block body.
 */
final class CatchThrowCollector
{
    /**
     * Returns the throw statements that belong to the given catch body.
     *
     * @param array<\PhpParser\Node\Stmt> $stmts
     * @return list<\PhpParser\Node>
     */
    public function collect(array $stmts): array
    {
        $traverser = new NodeTraverser();
        $visitor = new CatchThrowVisitor();
        $traverser->addVisitor($visitor);
        $traverser->traverse($stmts);

        return $visitor->throws();
    }
}
