<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ClassPair;

/**
 * Matches fully qualified class names against glob patterns where "*" spans namespace separators.
 */
final class ClassNameGlob
{
    /**
     * @var list<string>
     * @readonly
     */
    private array $expressions;

    /**
     * @param list<string> $patterns class name globs; "*" matches any text and "?" one character
     */
    public function __construct(array $patterns)
    {
        $expressions = [];
        foreach ($patterns as $pattern) {
            $expressions[] = '/^' . str_replace(['\*', '\?'], ['.*', '.'], preg_quote($pattern, '/')) . '$/D';
        }

        $this->expressions = $expressions;
    }

    /**
     * Reports whether any pattern matches the class name.
     */
    public function matches(string $class): bool
    {
        foreach ($this->expressions as $expression) {
            if (preg_match($expression, $class) === 1) {
                return true;
            }
        }

        return false;
    }
}
