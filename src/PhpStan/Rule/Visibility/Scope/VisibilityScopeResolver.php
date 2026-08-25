<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Visibility\Scope;

use function array_unique;
use function array_values;
use function in_array;
use function ltrim;
use function preg_match;
use function str_contains;
use function str_starts_with;
use function strtolower;

/**
 * Resolves the namespace scope described by @visibility tags.
 */
final class VisibilityScopeResolver
{
    /** @var list<string> */
    private const KEYWORDS = ['public', 'namespace', 'parent', 'root'];

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
     * Resolves the scope of a declaration in the given namespace.
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
     * Resolves one value to the namespace it opens, or null when it opens nothing.
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
     * Returns the keyword a value names, or null for a namespace value.
     */
    public function keywordOf(string $value): ?string
    {
        if (str_starts_with($value, '\\')) {
            return null;
        }

        $keyword = strtolower($value);

        return in_array($keyword, self::KEYWORDS, true) ? $keyword : null;
    }

    /**
     * Returns every keyword named by the values.
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
     * Reports whether the value has the one-word shape reserved for keywords.
     */
    public function isKeywordShape(string $value): bool
    {
        return !str_contains($value, '\\') && strtolower($value) === $value;
    }

    /**
     * Reports whether the value is a syntactically valid namespace name.
     */
    public function isNamespaceName(string $value): bool
    {
        return preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*(?:\\\\[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)*$/', $value) === 1;
    }

    /**
     * Drops namespaces already covered by a broader namespace in the list.
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
}
