<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;

/**
 * Collects missing PHPDoc errors across public API declaration surfaces.
 */
final class PublicApiPhpDocErrorCollector
{
    private readonly PublicApiClassPhpDocErrorCollector $classCollector;

    private readonly PublicApiMethodPhpDocErrorCollector $methodCollector;

    private readonly PublicApiPropertyPhpDocErrorCollector $propertyCollector;

    private readonly PublicApiConstantPhpDocErrorCollector $constantCollector;

    /**
     * Creates a public API PHPDoc collector from declaration-specific collectors.
     */
    public function __construct(
        ?PublicApiClassPhpDocErrorCollector $classCollector = null,
        ?PublicApiMethodPhpDocErrorCollector $methodCollector = null,
        ?PublicApiPropertyPhpDocErrorCollector $propertyCollector = null,
        ?PublicApiConstantPhpDocErrorCollector $constantCollector = null,
    ) {
        $this->classCollector = $classCollector ?? new PublicApiClassPhpDocErrorCollector();
        $this->methodCollector = $methodCollector ?? new PublicApiMethodPhpDocErrorCollector();
        $this->propertyCollector = $propertyCollector ?? new PublicApiPropertyPhpDocErrorCollector();
        $this->constantCollector = $constantCollector ?? new PublicApiConstantPhpDocErrorCollector();
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(
        \PhpParser\Node\Stmt\ClassLike $node,
        string $kindLabel,
        string $className,
    ): array {
        return array_merge(
            $this->classCollector->errors($node, $kindLabel, $className),
            $this->methodCollector->errors($node, $className),
            $this->propertyCollector->errors($node, $className),
            $this->constantCollector->errors($node, $className),
        );
    }
}
