<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\NamespaceVisibility;

use function array_unique;
use function array_values;
use function in_array;
use function ltrim;
use function preg_match;
use function str_contains;
use function str_starts_with;
use function strtolower;

/**
 * Turns the @visibility tags of a declaration into the scope they describe.
 */
final class VisibilityScopeResolver
{
    /** @readonly */
    private VisibilityTagParser $tagParser;

    /** @readonly */
    private NamespaceLineage $lineage;

    /**
     * Creates the resolver from tag reading and namespace ancestry.
     */
    public function __construct(?VisibilityTagParser $tagParser = null, ?NamespaceLineage $lineage = null)
    {
        $this->tagParser = $tagParser ?? new VisibilityTagParser();
        $this->lineage = $lineage ?? new NamespaceLineage();
    }

    /**
     * Resolves the scope a PHPDoc comment declares for a symbol in the given namespace.
     *
     * A declaration is always visible inside its own namespace, so that namespace joins
     * whatever the tags open up. Values that resolve to nothing are left to
     * VisibilityTagValidator to report; a declaration whose tags all fail to resolve stays
     * unrestricted, so one malformed tag produces one error instead of an error per usage.
     */
    public function resolve(?string $docComment, string $declaringNamespace): VisibilityScope
    {
        $values = $this->tagParser->values($docComment);
        if ($values === [] || in_array('public', $this->keywordsOf($values), true)) {
            return new VisibilityScope([], $values, false, $this->lineage);
        }

        $allowedNamespaces = [];
        foreach ($values as $value) {
            $namespace = $this->namespaceFor($value, $declaringNamespace);
            if ($namespace !== null) {
                $allowedNamespaces[] = $namespace;
            }
        }

        if ($allowedNamespaces === []) {
            return new VisibilityScope([], $values, false, $this->lineage);
        }

        if ($declaringNamespace !== '') {
            $allowedNamespaces[] = $declaringNamespace;
        }

        return new VisibilityScope($this->collapse($allowedNamespaces), $values, true, $this->lineage);
    }

    /**
     * Drops every namespace that another namespace in the list already covers.
     *
     * A declaration is always visible in its own namespace, so that namespace joins every
     * resolved scope and would otherwise be listed next to the wider scope containing it.
     *
     * @param list<string> $namespaces
     * @return list<string>
     */
    public function collapse(array $namespaces): array
    {
        $collapsed = [];
        foreach (array_values(array_unique($namespaces)) as $candidate) {
            $covered = false;
            foreach ($namespaces as $other) {
                if ($other !== $candidate && $this->lineage->covers($other, $candidate)) {
                    $covered = true;
                    break;
                }
            }

            if (!$covered) {
                $collapsed[] = $candidate;
            }
        }

        return $collapsed;
    }

    /**
     * Resolves a single @visibility value to the namespace it opens up, or null when it resolves to nothing.
     */
    public function namespaceFor(string $value, string $declaringNamespace): ?string
    {
        $keyword = $this->keywordOf($value);
        if ($keyword === 'namespace') {
            return $declaringNamespace;
        }

        if ($keyword === 'parent') {
            return $this->lineage->parentOf($declaringNamespace);
        }

        if ($keyword === 'root') {
            return $this->lineage->rootOf($declaringNamespace);
        }

        if ($keyword !== null || $this->isKeywordShape($value)) {
            return null;
        }

        $namespace = ltrim($value, '\\');

        return $this->isNamespaceName($namespace) ? $namespace : null;
    }

    /**
     * Reports whether a value has the shape reserved for scope keywords: one bare lowercase word.
     *
     * A namespace of that shape is written with a leading backslash, which is also how a
     * mistyped keyword is told apart from the namespace it would otherwise name.
     */
    public function isKeywordShape(string $value): bool
    {
        return !str_contains($value, '\\') && strtolower($value) === $value;
    }

    /**
     * Returns the scope keyword a value names, or null when the value names a namespace.
     *
     * A leading backslash always means "namespace", which is how a namespace that happens
     * to be called Root or Parent is written.
     */
    public function keywordOf(string $value): ?string
    {
        if (str_starts_with($value, '\\')) {
            return null;
        }

        $keyword = strtolower($value);

        return in_array($keyword, ['public', 'namespace', 'parent', 'root'], true) ? $keyword : null;
    }

    /**
     * Returns the keyword of every value that names one.
     *
     * @param list<string> $values
     * @return list<string>
     */
    public function keywordsOf(array $values): array
    {
        $keywords = [];
        foreach ($values as $value) {
            $keyword = $this->keywordOf($value);
            if ($keyword !== null) {
                $keywords[] = $keyword;
            }
        }

        return $keywords;
    }

    /**
     * Reports whether the value is a syntactically valid namespace name.
     */
    public function isNamespaceName(string $value): bool
    {
        return preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*(?:\\\\[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)*$/', $value) === 1;
    }
}
