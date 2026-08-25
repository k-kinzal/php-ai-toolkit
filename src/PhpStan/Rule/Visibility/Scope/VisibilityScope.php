<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Visibility\Scope;

use function implode;
use function sprintf;

/**
 * A resolved @visibility declaration and the namespaces it admits.
 */
final class VisibilityScope
{
    /** @readonly */
    private NamespaceLineage $lineage;

    /**
     * @param list<string> $allowedNamespaces namespaces whose subtree may name the declaration
     * @param list<string> $declaredValues values exactly as written in PHPDoc
     */
    public function __construct(
        /** @readonly */
        private array $allowedNamespaces,
        /** @readonly */
        private array $declaredValues,
        /** @readonly */
        private bool $restricted,
        ?NamespaceLineage $lineage = null,
    ) {
        $this->lineage = $lineage ?? new NamespaceLineage();
    }

    /**
     * Returns the values exactly as declared.
     *
     * @return list<string>
     */
    public function declaredValues(): array
    {
        return $this->declaredValues;
    }

    /**
     * Reports whether code in the given namespace may name the declaration.
     */
    public function permits(string $callerNamespace): bool
    {
        if (!$this->restricted) {
            return true;
        }

        foreach ($this->allowedNamespaces as $allowedNamespace) {
            if ($this->lineage->covers($allowedNamespace, $callerNamespace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the declared tags quoted for an error message.
     */
    public function describeTags(): string
    {
        $quoted = [];
        foreach ($this->declaredValues as $declaredValue) {
            $quoted[] = sprintf('"@visibility %s"', $declaredValue);
        }

        return implode(' and ', $quoted);
    }

    /**
     * Returns the namespaces admitted by this scope for an error message.
     */
    public function describeAllowed(): string
    {
        $quoted = [];
        foreach ($this->allowedNamespaces as $allowedNamespace) {
            $quoted[] = sprintf('"%s"', $allowedNamespace);
        }

        if ($quoted === [] || isset($quoted[1])) {
            return sprintf('namespaces %s and their sub-namespaces', implode(', ', $quoted));
        }

        return sprintf('namespace %s and its sub-namespaces', $quoted[0]);
    }
}
