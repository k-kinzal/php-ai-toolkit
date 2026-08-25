<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;

/**
 * Finds mixed positions imposed by inherited method contracts.
 *
 * @visibility namespace
 */
final class InheritedMixedContractInspector
{
    /** @readonly */
    private ConcreteMixedTypeInspector $typeInspector;

    /**
     * Creates the inspector from concrete-mixed detection.
     */
    public function __construct(?ConcreteMixedTypeInspector $typeInspector = null)
    {
        $this->typeInspector = $typeInspector ?? new ConcreteMixedTypeInspector();
    }

    /**
     * Reports whether an inherited contract requires mixed at one parameter position.
     */
    public function allowsParameter(ClassReflection $class, string $methodName, int $position): bool
    {
        foreach ($this->contracts($class, $methodName) as $contract) {
            foreach ($contract->getVariants() as $variant) {
                $parameters = $variant->getParameters();
                if (isset($parameters[$position]) && $this->typeInspector->contains($parameters[$position]->getType())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Reports whether an inherited contract requires a mixed return.
     */
    public function allowsReturn(ClassReflection $class, string $methodName): bool
    {
        foreach ($this->contracts($class, $methodName) as $contract) {
            foreach ($contract->getVariants() as $variant) {
                if ($this->typeInspector->contains($variant->getReturnType())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns parent, interface, and abstract-trait method contracts.
     *
     * @return list<ExtendedMethodReflection>
     */
    public function contracts(ClassReflection $class, string $methodName): array
    {
        $contracts = [];
        for ($parent = $class->getParentClass(); $parent !== null; $parent = $parent->getParentClass()) {
            $this->appendContract($contracts, $parent, $methodName, false);
        }
        foreach ($class->getInterfaces() as $interface) {
            $this->appendContract($contracts, $interface, $methodName, false);
        }
        foreach ($class->getTraits(true) as $trait) {
            $this->appendContract($contracts, $trait, $methodName, true);
        }

        return $contracts;
    }

    /**
     * Adds one usable method declared by an ancestor.
     *
     * @param list<ExtendedMethodReflection> $contracts
     */
    public function appendContract(array &$contracts, ClassReflection $ancestor, string $methodName, bool $abstractOnly): void
    {
        if (!$ancestor->hasNativeMethod($methodName)) {
            return;
        }

        $method = $ancestor->getNativeMethod($methodName);
        $abstract = $method->isAbstract();
        $isAbstract = is_bool($abstract) ? $abstract : $abstract->yes();
        if ($method->isPrivate() || ($abstractOnly && !$isAbstract)) {
            return;
        }

        $contracts[] = $method;
    }
}
