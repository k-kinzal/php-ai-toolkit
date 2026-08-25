<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Analysis\Scope;

use function explode;
use function implode;
use function ltrim;
use function str_starts_with;
use function strrpos;
use function substr;

/**
 * Answers the namespace ancestry questions a visibility scope is built from.
 *
 * A namespace behaves like a Rust module: it holds its own declarations and,
 * transitively, everything declared below it. Every scope ScopeGuard resolves is
 * one namespace plus that subtree, so ancestry is the only arithmetic needed.
 *
 * @visibility parent
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
     *
     * A namespace with a single segment reports the global namespace as its parent,
     * which callers have to recognise as "no restriction left to express".
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
        if ($namespace === '') {
            return null;
        }

        return explode('\\', $namespace)[0];
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
