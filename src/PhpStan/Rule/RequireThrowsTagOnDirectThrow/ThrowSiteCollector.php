<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\RequireThrowsTagOnDirectThrow;

use PhpParser\NodeTraverser;

/**
 * Collects the escaping throw sites of one method body.
 */
final class ThrowSiteCollector
{
    /**
     * Returns every throw site in the statements that the method itself does not catch.
     *
     * @param array<\PhpParser\Node\Stmt> $stmts
     * @return list<ThrowSite>
     */
    public function collect(array $stmts): array
    {
        $traverser = new NodeTraverser();
        $visitor = new ThrowSiteVisitor();
        $traverser->addVisitor($visitor);
        $traverser->traverse($stmts);

        return $visitor->sites();
    }
}
