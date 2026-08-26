<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Extension\RedundantDiagnostic;

use function array_values;
use function is_array;
use function method_exists;

use PhpParser\Node;
use Toolkit\PhpStan\Rule\ExceptionHandling\ThrowSiteCollector;

/**
 * Identifies PHPStan checked-exception errors replaced by the direct-throw rule.
 */
final class DirectThrowDiagnosticPolicy
{
    /** @readonly */
    private ThrowSiteCollector $throwSiteCollector;

    /**
     * Creates the policy from direct throw-site collection.
     */
    public function __construct(?ThrowSiteCollector $throwSiteCollector = null)
    {
        $this->throwSiteCollector = $throwSiteCollector ?? new ThrowSiteCollector();
    }

    /**
     * Reports whether a checked-exception error points to a direct escaping throw.
     */
    public function isRedundant(?string $identifier, ?int $line, Node $node): bool
    {
        if ($identifier !== 'missingType.checkedException' || $line === null) {
            return false;
        }

        foreach ($this->throwSiteCollector->collect($this->statements($node)) as $site) {
            if ($site->line === $line) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns method statements from parser and PHPStan virtual method nodes.
     *
     * @return list<Node\Stmt>
     */
    public function statements(Node $node): array
    {
        if ($node instanceof Node\Stmt\ClassMethod) {
            return array_values($node->stmts ?? []);
        }

        if ($node->getType() !== 'PHPStan_Node_MethodReturnStatementsNode' || !method_exists($node, 'getStatements')) {
            return [];
        }

        $statements = $node->getStatements();
        if (!is_array($statements)) {
            return [];
        }

        $methodStatements = [];
        foreach ($statements as $statement) {
            if ($statement instanceof Node\Stmt) {
                $methodStatements[] = $statement;
            }
        }

        return $methodStatements;
    }
}
