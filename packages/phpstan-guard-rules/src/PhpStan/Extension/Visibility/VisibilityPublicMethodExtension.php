<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Extension\Visibility;

use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Methods\AlwaysUsedMethodExtension;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector;

/**
 * Marks @visibility public methods as always used for PHPStan's dead-code rules.
 */
final class VisibilityPublicMethodExtension implements AlwaysUsedMethodExtension
{
    /**
     * Creates the extension from public API tag detection.
     */
    public function __construct(
        /** @readonly */
        private PublicApiVisibilityDetector $visibilityDetector,
    ) {
    }

    /**
     * Reports whether the method is an explicitly public API declaration.
     */
    public function isAlwaysUsed(MethodReflection $methodReflection): bool
    {
        return $this->visibilityDetector->declaresPublic($methodReflection->getDocComment());
    }
}
