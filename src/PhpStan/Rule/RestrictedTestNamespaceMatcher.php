<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Matches class-like names against restricted test namespace prefixes.
 */
final class RestrictedTestNamespaceMatcher
{
    /**
     * @var list<string>
     * @readonly
     */
    private array $prefixes;

    /**
     * @param list<string> $prefixes namespace prefixes for restricted test classes
     */
    public function __construct(array $prefixes = ['Tests\\Unit', 'Tests\\Integration'])
    {
        $this->prefixes = array_map(
            static fn (string $prefix): string => rtrim($prefix, '\\') . '\\',
            $prefixes,
        );
    }

    /**
     * Reports whether the class-like node is in a restricted test namespace.
     */
    public function matches(\PhpParser\Node\Stmt\ClassLike $node): bool
    {
        if (!isset($node->namespacedName)) {
            return false;
        }

        $fqcn = $node->namespacedName->toString();

        foreach ($this->prefixes as $prefix) {
            if (str_starts_with($fqcn, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
