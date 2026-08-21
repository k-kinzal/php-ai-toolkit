<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Analysis\Scope;

use function preg_match_all;
use function str_contains;

/**
 * Reads the @visibility scope values written in a PHPDoc comment.
 *
 * The tag is read from the raw comment text: it is ScopeGuard's own tag, so no
 * PHPDoc parser knows it, and both supported phpstan/phpdoc-parser majors expose
 * a different parser constructor.
 *
 * @visibility parent
 */
final class VisibilityTagParser
{
    private const TAG = '@visibility';

    /**
     * Returns every scope value declared with the visibility tag, in source order.
     *
     * A tag only counts where PHPDoc puts one: at the start of a comment line. Prose
     * that names the tag while explaining it is text, not a declaration.
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
