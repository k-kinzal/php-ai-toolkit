<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Analysis\Scope;

/**
 * Holds the namespaces whose code may name any declaration regardless of its scope.
 *
 * PHP has no counterpart to a test module declared inside the module it covers, so a
 * unit test of a namespace-scoped class always sits outside the scope it exercises.
 * The namespaces allowed to reach in anyway are named in configuration.
 *
 * @visibility parent
 */
final class ExemptNamespaces
{
    /**
     * @var list<string>
     * @readonly
     */
    private array $prefixes;

    /** @readonly */
    private NamespaceLineage $lineage;

    /**
     * @param list<string> $prefixes namespace prefixes whose subtree is exempt
     */
    public function __construct(array $prefixes = [], ?NamespaceLineage $lineage = null)
    {
        $this->prefixes = $prefixes;
        $this->lineage = $lineage ?? new NamespaceLineage();
    }

    /**
     * Reports whether code in the given namespace is exempt from scope checks.
     */
    public function contains(string $namespace): bool
    {
        foreach ($this->prefixes as $prefix) {
            if ($prefix !== '' && $this->lineage->covers($prefix, $namespace)) {
                return true;
            }
        }

        return false;
    }
}
