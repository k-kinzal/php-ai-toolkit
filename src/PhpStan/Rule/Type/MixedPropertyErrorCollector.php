<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\Type;

use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\IdentifierRuleError;

use function sprintf;

/**
 * Collects mixed diagnostics from stored property contracts.
 *
 * @visibility namespace
 */
final class MixedPropertyErrorCollector
{
    /** @readonly */
    private ConcreteMixedTypeInspector $typeInspector;

    /** @readonly */
    private MixedVisibilityDetector $visibilityDetector;

    /** @readonly */
    private MixedTypeErrorBuilder $errorBuilder;

    /**
     * Creates the collector from type and visibility policies.
     */
    public function __construct(
        ?ConcreteMixedTypeInspector $typeInspector = null,
        ?MixedVisibilityDetector $visibilityDetector = null,
        ?MixedTypeErrorBuilder $errorBuilder = null,
    ) {
        $this->typeInspector = $typeInspector ?? new ConcreteMixedTypeInspector();
        $this->visibilityDetector = $visibilityDetector ?? new MixedVisibilityDetector();
        $this->errorBuilder = $errorBuilder ?? new MixedTypeErrorBuilder();
    }

    /**
     * Collects an error from one internal property declaration.
     *
     * @return list<IdentifierRuleError>
     */
    public function errors(ClassPropertyNode $node): array
    {
        $class = $node->getClassReflection();
        $restricted = $node->isPrivate()
            || $this->visibilityDetector->classIsRestricted($class)
            || $this->visibilityDetector->isRestricted(
                $node->getPhpDoc(),
                $class->getNativeReflection()->getNamespaceName()
            );
        if (!$restricted) {
            return [];
        }

        $type = $node->getPhpDocType() ?? $node->getNativeType();
        if ($type === null || !$this->typeInspector->contains($type)) {
            return [];
        }

        return [$this->errorBuilder->build(
            $this->typeInspector->describe($type),
            'property type',
            sprintf('%s::$%s', $class->getDisplayName(), $node->getName()),
            $node->getOriginalNode()->getStartLine()
        )];
    }
}
