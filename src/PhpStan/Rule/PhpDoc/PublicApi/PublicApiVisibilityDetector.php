<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\PhpDoc\PublicApi;

use function preg_match;
use function str_contains;

/**
 * Detects the declarations a PHPDoc block states are public API.
 *
 * The statement is the "@visibility public" tag. Leaving the tag off also
 * leaves a declaration reachable from everywhere, but it says nothing about
 * intent; writing the tag is how a project declares that a symbol is part of
 * the surface other code is invited to use, and that is the surface examples
 * are required on.
 */
final class PublicApiVisibilityDetector
{
    private const TAG = '@visibility';

    /**
     * Reports whether the PHPDoc block declares the symbol public API.
     *
     * A tag only counts where PHPDoc puts one, at the start of a comment line,
     * so prose that names the tag while explaining it is text and not a
     * declaration.
     */
    public function declaresPublic(?string $docComment): bool
    {
        if ($docComment === null || !str_contains($docComment, self::TAG)) {
            return false;
        }

        return preg_match('/^[ \t]*(?:\/\*\*[ \t]*)?\*?[ \t]*' . self::TAG . '[ \t]+public[ \t]*(?:\*\/)?[ \t]*$/m', $docComment) === 1;
    }
}
