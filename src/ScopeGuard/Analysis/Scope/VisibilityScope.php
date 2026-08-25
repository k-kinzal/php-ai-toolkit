<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Analysis\Scope;

use function implode;
use function sprintf;

/**
 * A resolved @visibility declaration: the namespaces a symbol may be named from.
 *
 * @property-read list<string> $allowedNamespaces
 * @property-read list<string> $declaredValues
 * @property-read bool $restricted
 *
 * @visibility parent
 */
final class VisibilityScope
{
    /** @readonly */
    private NamespaceLineage $lineage;

    /**
     * @param list<string> $allowedNamespaces namespaces whose subtree may name the symbol
     * @param list<string> $declaredValues @visibility values exactly as written in the PHPDoc
     * @param bool $restricted whether the declaration narrows visibility at all
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
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'allowedNamespaces' => $this->allowedNamespaces,
            'declaredValues' => $this->declaredValues,
            'restricted' => $this->restricted,
            default => null,
        };
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
     * Returns the declared tags as written, quoted for a report message.
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
     * Returns the namespaces the declaration is visible in, phrased for a report message.
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
