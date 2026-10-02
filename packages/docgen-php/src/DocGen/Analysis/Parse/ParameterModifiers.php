<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Parse;

/**
 * Constructor promotion flag values of php-parser parameter nodes.
 *
 * The numeric values are stable across php-parser 4 and 5, which both keep
 * them compatible with the reflection modifier bits.
 */
final class ParameterModifiers
{
    /**
     * Flag of a public promoted constructor parameter.
     */
    public const VISIBILITY_PUBLIC = 1;

    /**
     * Flag of a protected promoted constructor parameter.
     */
    public const VISIBILITY_PROTECTED = 2;

    /**
     * Flag of a private promoted constructor parameter.
     */
    public const VISIBILITY_PRIVATE = 4;

    /**
     * Flag of a readonly promoted constructor parameter.
     */
    public const READONLY = 64;

    /**
     * Returns the promoted visibility name for a parameter flag set.
     */
    public function promotedVisibility(int $flags): ?string
    {
        if (($flags & self::VISIBILITY_PUBLIC) !== 0) {
            return 'public';
        }

        if (($flags & self::VISIBILITY_PROTECTED) !== 0) {
            return 'protected';
        }

        if (($flags & self::VISIBILITY_PRIVATE) !== 0) {
            return 'private';
        }

        return null;
    }
}
