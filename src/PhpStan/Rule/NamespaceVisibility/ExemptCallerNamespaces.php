<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\NamespaceVisibility;

/**
 * Holds the namespaces whose code is never checked against a @visibility scope.
 *
 * PHP has no equivalent of a test module declared inside the module it covers, so a unit
 * test of a namespace-scoped class always sits outside the scope it exercises. The
 * namespaces that are allowed to reach in anyway are named in configuration.
 */
final class ExemptCallerNamespaces
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
     * Reports whether code in the given namespace is exempt from visibility checks.
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
