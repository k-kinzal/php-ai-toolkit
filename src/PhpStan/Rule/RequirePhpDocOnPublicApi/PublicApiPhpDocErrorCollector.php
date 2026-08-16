<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\RequirePhpDocOnPublicApi;

use PhpAiToolkit\PhpStan\Rule\Shared\LineOrderedErrors;
use PHPStan\Rules\IdentifierRuleError;

/**
 * Collects missing PHPDoc errors across public API declaration surfaces.
 */
final class PublicApiPhpDocErrorCollector
{
    /** @readonly */
    private PublicApiClassPhpDocErrorCollector $classCollector;

    /** @readonly */
    private PublicApiMethodPhpDocErrorCollector $methodCollector;

    /** @readonly */
    private PublicApiPropertyPhpDocErrorCollector $propertyCollector;

    /** @readonly */
    private PublicApiConstantPhpDocErrorCollector $constantCollector;

    /** @readonly */
    private LineOrderedErrors $order;

    /**
     * Creates a public API PHPDoc collector from declaration-specific collectors.
     */
    public function __construct(
        ?PublicApiClassPhpDocErrorCollector $classCollector = null,
        ?PublicApiMethodPhpDocErrorCollector $methodCollector = null,
        ?PublicApiPropertyPhpDocErrorCollector $propertyCollector = null,
        ?PublicApiConstantPhpDocErrorCollector $constantCollector = null,
        ?LineOrderedErrors $order = null,
    ) {
        $this->classCollector = $classCollector ?? new PublicApiClassPhpDocErrorCollector();
        $this->methodCollector = $methodCollector ?? new PublicApiMethodPhpDocErrorCollector();
        $this->propertyCollector = $propertyCollector ?? new PublicApiPropertyPhpDocErrorCollector();
        $this->constantCollector = $constantCollector ?? new PublicApiConstantPhpDocErrorCollector();
        $this->order = $order ?? new LineOrderedErrors();
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(
        \PhpParser\Node\Stmt\ClassLike $node,
        string $kindLabel,
        string $className,
    ): array {
        return $this->order->sorted(array_merge(
            $this->classCollector->errors($node, $kindLabel, $className),
            $this->methodCollector->errors($node, $className),
            $this->propertyCollector->errors($node, $className),
            $this->constantCollector->errors($node, $className),
        ));
    }
}
