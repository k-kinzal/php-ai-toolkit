<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Visibility\Scope;

use function preg_match_all;
use function str_contains;

/**
 * Reads custom @visibility values from a raw PHPDoc comment.
 */
final class VisibilityTagParser
{
    private const TAG = '@visibility';

    /**
     * Returns every scope value in source order.
     *
     * @return list<string>
     */
    public function values(?string $docComment): array
    {
        if ($docComment === null || !str_contains($docComment, self::TAG)) {
            return [];
        }

        $matches = [];
        preg_match_all('/^[ \t]*(?:\/\*\*[ \t]*)?\*?[ \t]*' . self::TAG . '[ \t]+([^\s*]+)/m', $docComment, $matches);

        return $matches[1];
    }
}
