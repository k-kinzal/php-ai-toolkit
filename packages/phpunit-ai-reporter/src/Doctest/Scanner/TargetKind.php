<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Scanner;

/**
 * Represents the type of target that contains docblock examples.
 *
 * Used to categorize where examples are found in the source code.
 *
 * Ported from the TargetType enum of k-kinzal/doctest-php. This package
 * supports PHP 8.0, which has no enums, so the cases are class constants
 * carrying the same values the enum did. The name avoids the "Type" suffix the
 * toolkit forbids on class-likes.
 */
final class TargetKind
{
    /**
     * A docblock at the top of a file, documenting the file itself.
     */
    public const FILE = 'file';

    /**
     * A docblock on a class declaration.
     */
    public const CLASS_LIKE = 'class';

    /**
     * A docblock on a method of a class.
     */
    public const METHOD = 'method';

    /**
     * A docblock on a function.
     */
    public const FUNCTION = 'function';
}
