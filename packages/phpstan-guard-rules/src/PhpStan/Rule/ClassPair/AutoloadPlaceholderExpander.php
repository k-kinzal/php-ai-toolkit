<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ClassPair;

/**
 * Replaces "${directory}" placeholders with the namespace Composer maps to that directory.
 */
final class AutoloadPlaceholderExpander
{
    /**
     * @param ComposerAutoloadMap $autoload PSR-4 entries used to resolve placeholders
     */
    public function __construct(
        /** @readonly */
        private ComposerAutoloadMap $autoload,
    ) {
    }

    /**
     * Returns the pattern with every placeholder replaced, or null when a directory has no PSR-4 namespace.
     */
    public function expand(string $pattern): ?string
    {
        preg_match_all('/\$\{([^}]*)\}/', $pattern, $matches, PREG_SET_ORDER);

        $expanded = $pattern;
        foreach ($matches as $match) {
            $namespace = $this->autoload->namespaceForPath($match[1]);
            if ($namespace === null) {
                return null;
            }

            $expanded = str_replace($match[0], $namespace, $expanded);
        }

        return ltrim($expanded, '\\');
    }
}
