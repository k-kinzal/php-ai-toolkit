<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Visibility\Scope;

use function explode;
use function implode;
use function ltrim;
use function str_starts_with;
use function strrpos;
use function substr;

/**
 * Answers the namespace ancestry questions used by visibility scopes.
 */
final class NamespaceLineage
{
    /**
     * Returns the namespace part of a qualified name, empty for the global namespace.
     */
    public function of(string $qualifiedName): string
    {
        $name = ltrim($qualifiedName, '\\');
        $separator = strrpos($name, '\\');

        return $separator === false ? '' : substr($name, 0, $separator);
    }

    /**
     * Returns the namespace directly above the given one, or null for the global namespace.
     */
    public function parentOf(string $namespace): ?string
    {
        return $namespace === '' ? null : $this->of($namespace);
    }

    /**
     * Returns the outermost segment of the given namespace, or null for the global namespace.
     */
    public function rootOf(string $namespace): ?string
    {
        return $namespace === '' ? null : explode('\\', $namespace)[0];
    }

    /**
     * Reports whether a namespace is the scope namespace or lies below it.
     */
    public function covers(string $scopeNamespace, string $namespace): bool
    {
        if ($scopeNamespace === '') {
            return true;
        }

        return $namespace === $scopeNamespace || str_starts_with($namespace, $scopeNamespace . '\\');
    }

    /**
     * Returns the deepest namespace that covers both given namespaces.
     */
    public function commonAncestorOf(string $first, string $second): string
    {
        $firstSegments = $first === '' ? [] : explode('\\', $first);
        $secondSegments = $second === '' ? [] : explode('\\', $second);
        $sharedSegments = [];

        foreach ($firstSegments as $index => $segment) {
            if (($secondSegments[$index] ?? null) !== $segment) {
                break;
            }

            $sharedSegments[] = $segment;
        }

        return implode('\\', $sharedSegments);
    }
}
