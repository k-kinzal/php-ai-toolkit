<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Analysis\Parse;

use function array_merge;

/**
 * Groups the statements of one file by the namespace they are declared in.
 *
 * @visibility parent
 */
final class FileNamespaces
{
    /** @readonly */
    private NodeWalker $walker;

    /**
     * Creates the grouping from tree walking.
     */
    public function __construct(?NodeWalker $walker = null)
    {
        $this->walker = $walker ?? new NodeWalker();
    }

    /**
     * Returns every node of the file keyed by its declaring namespace.
     *
     * Files without a namespace declaration report the global namespace as an
     * empty string, and repeated blocks of one namespace are merged.
     *
     * @param list<\PhpParser\Node\Stmt> $statements
     * @return array<string, list<\PhpParser\Node>>
     */
    public function groups(array $statements): array
    {
        $groups = [];
        $global = [];

        foreach ($statements as $statement) {
            if (!$statement instanceof \PhpParser\Node\Stmt\Namespace_) {
                $global[] = $statement;
                continue;
            }

            $name = $statement->name === null ? '' : $statement->name->toString();
            $groups[$name] = array_merge($groups[$name] ?? [], $this->walker->walk($statement->stmts));
        }

        if ($global !== []) {
            $groups[''] = array_merge($groups[''] ?? [], $this->walker->walk($global));
        }

        return $groups;
    }
}
