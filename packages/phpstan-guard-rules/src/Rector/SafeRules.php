<?php

declare(strict_types=1);

namespace Toolkit\Rector;

use Rector\Contract\Rector\RectorInterface;

/**
 * Groups the transformations classified as safe for their supported cases.
 */
final class SafeRules
{
    /**
     * Returns the safe rule classes for Rector's withRules() configuration.
     *
     * @return list<class-string<RectorInterface>>
     */
    public static function rules(): array
    {
        return [
            ForbidSingleLinePhpDocRector::class,
            OverrideMustHaveAttributeRector::class,
        ];
    }
}
