<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\NamespaceVisibility;

use function implode;
use function sprintf;

/**
 * A resolved @visibility declaration: the namespaces a symbol may be used from.
 */
final class VisibilityScope
{
    /**
     * @var list<string>
     * @readonly
     */
    private array $allowedNamespaces;

    /**
     * @var list<string>
     * @readonly
     */
    private array $declaredValues;

    /** @readonly */
    private bool $restricted;

    /** @readonly */
    private NamespaceLineage $lineage;

    /**
     * @param list<string> $allowedNamespaces namespaces whose subtree may use the symbol
     * @param list<string> $declaredValues @visibility values exactly as written in the PHPDoc
     * @param bool $restricted whether the declaration narrows visibility at all
     */
    public function __construct(
        array $allowedNamespaces,
        array $declaredValues,
        bool $restricted,
        ?NamespaceLineage $lineage = null,
    ) {
        $this->allowedNamespaces = $allowedNamespaces;
        $this->declaredValues = $declaredValues;
        $this->restricted = $restricted;
        $this->lineage = $lineage ?? new NamespaceLineage();
    }

    /**
     * Reports whether the declaration narrows visibility at all.
     */
    public function isRestricted(): bool
    {
        return $this->restricted;
    }

    /**
     * Reports whether code in the given namespace may use the declaration.
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
     * Returns the namespaces whose subtree may use the declaration.
     *
     * @return list<string>
     */
    public function allowedNamespaces(): array
    {
        return $this->allowedNamespaces;
    }

    /**
     * Returns the declared tags as written, quoted for an error message.
     */
    public function describeTags(): string
    {
        $quoted = [];
        foreach ($this->declaredValues as $declaredValue) {
            $quoted[] = sprintf('"@visibility %s"', $declaredValue);
        }

        return implode(' and ', $quoted);
    }
}
