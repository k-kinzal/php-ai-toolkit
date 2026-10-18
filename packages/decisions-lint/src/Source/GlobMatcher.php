<?php

declare(strict_types=1);

namespace DecisionsLint\Source;

/**
 * Matches relative paths against the globs rules and the configuration use.
 *
 * `*` matches within one path segment, `**` matches any number of segments,
 * `?` matches one character. A pattern without a wildcard also matches every
 * path below the directory it names, so `src` selects `src/App/Foo.php`.
 */
final class GlobMatcher
{
    /**
     * Tells whether a relative path matches a glob.
     */
    public function matches(string $pattern, string $path): bool
    {
        $pattern = trim(str_replace('\\', '/', $pattern), '/');
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($pattern === '' || $pattern === '.') {
            return true;
        }
        if ($path === $pattern || str_starts_with($path, $pattern . '/')) {
            return true;
        }

        return preg_match($this->regex($pattern), $path) === 1;
    }

    /**
     * Tells whether a relative path matches any glob of a list.
     *
     * @param list<string> $patterns
     */
    public function matchesAny(array $patterns, string $path): bool
    {
        foreach ($patterns as $pattern) {
            if ($this->matches($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the leading directory of a glob that contains no wildcard, or an empty string.
     */
    public function base(string $pattern): string
    {
        $segments = [];
        foreach (explode('/', trim(str_replace('\\', '/', $pattern), '/')) as $segment) {
            if (strpbrk($segment, '*?[') !== false) {
                break;
            }
            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    /**
     * Translates a glob into an anchored regular expression.
     */
    public function regex(string $pattern): string
    {
        $regex = '';
        $length = strlen($pattern);
        for ($i = 0; $i < $length; ++$i) {
            $char = $pattern[$i];
            if ($char === '*' && ($pattern[$i + 1] ?? '') === '*') {
                $slash = ($pattern[$i + 2] ?? '') === '/';
                $regex .= $slash ? '(?:.*/)?' : '.*';
                $i += $slash ? 2 : 1;
            } elseif ($char === '*') {
                $regex .= '[^/]*';
            } elseif ($char === '?') {
                $regex .= '[^/]';
            } else {
                $regex .= preg_quote($char, '#');
            }
        }

        return '#^' . $regex . '$#';
    }
}
