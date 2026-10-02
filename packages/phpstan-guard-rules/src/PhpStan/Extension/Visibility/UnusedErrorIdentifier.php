<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Extension\Visibility;

use function in_array;
use function str_contains;
use function strtolower;

/**
 * Recognizes PHPStan and extension identifiers for unused declarations.
 */
final class UnusedErrorIdentifier
{
    /** @var list<string> */
    private const PROPERTY_USAGE_IDENTIFIERS = [
        'property.neverread',
        'property.neverwritten',
        'property.onlyread',
        'property.onlywritten',
    ];

    /**
     * Reports whether the identifier describes an unused declaration.
     */
    public function matches(?string $identifier): bool
    {
        if ($identifier === null) {
            return false;
        }

        $normalized = strtolower($identifier);

        return str_contains($normalized, 'unused')
            || str_contains($normalized, '.dead')
            || in_array($normalized, self::PROPERTY_USAGE_IDENTIFIERS, true);
    }
}
