<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Extension\Visibility;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector;

/**
 * Marks @visibility public properties as read, written, and initialized.
 */
final class VisibilityPublicPropertyExtension implements ReadWritePropertiesExtension
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
     * Reports whether the property is always read by public consumers.
     */
    public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
    {
        unset($propertyName);

        return $this->visibilityDetector->declaresPublic($property->getDocComment());
    }

    /**
     * Reports whether the property is always written by public consumers.
     */
    public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
    {
        unset($propertyName);

        return $this->visibilityDetector->declaresPublic($property->getDocComment());
    }

    /**
     * Reports whether the property is initialized outside analysed code.
     */
    public function isInitialized(PropertyReflection $property, string $propertyName): bool
    {
        unset($propertyName);

        return $this->visibilityDetector->declaresPublic($property->getDocComment());
    }
}
