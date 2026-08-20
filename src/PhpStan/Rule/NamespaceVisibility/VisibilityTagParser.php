<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\NamespaceVisibility;

use function preg_match_all;
use function str_contains;

/**
 * Reads the @visibility scope values written in a PHPDoc comment.
 *
 * The tag is read straight from the raw comment text rather than from a resolved
 * PHPDoc block, because analyzers only resolve the tags they know, and both
 * supported phpstan/phpdoc-parser majors expose a different parser constructor.
 */
final class VisibilityTagParser
{
    /**
     * Returns every scope value declared with @visibility, in source order.
     *
     * @return list<string>
     */
    public function values(?string $docComment): array
    {
        if ($docComment === null || !str_contains($docComment, '@visibility')) {
            return [];
        }

        $matches = [];
        preg_match_all('/@visibility[ \t]+([^\s*]+)/', $docComment, $matches);

        return $matches[1];
    }
}
