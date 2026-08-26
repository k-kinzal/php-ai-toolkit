<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Extension\Visibility;

use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtension;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector;

/**
 * Marks @visibility public class constants as always used.
 */
final class VisibilityPublicConstantExtension implements AlwaysUsedClassConstantsExtension
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
     * Reports whether the constant is an explicitly public API declaration.
     */
    public function isAlwaysUsed(ClassMemberReflection $constant): bool
    {
        return $this->visibilityDetector->declaresPublic($constant->getDocComment());
    }
}
