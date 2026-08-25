<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Analysis\Scope;

use function ltrim;
use function sprintf;

/**
 * Explains why an @visibility value cannot be honoured as written.
 *
 * A scope that resolves to nothing is left unenforced on purpose, so a tag nobody
 * can honour has to be reported here or it would silently drop the restriction it
 * was written to add.
 *
 * @visibility parent
 */
final class ScopeProblemReader
{
    /** @readonly */
    private VisibilityScopeResolver $scopeResolver;

    /** @readonly */
    private NamespaceLineage $lineage;

    /**
     * Creates the reader from scope resolution and namespace ancestry.
     */
    public function __construct(?VisibilityScopeResolver $scopeResolver = null, ?NamespaceLineage $lineage = null)
    {
        $this->scopeResolver = $scopeResolver ?? new VisibilityScopeResolver();
        $this->lineage = $lineage ?? new NamespaceLineage();
    }

    /**
     * Returns why a scope value cannot be honoured, or null when it can.
     */
    public function problem(string $value, string $declaringNamespace): ?string
    {
        $keyword = $this->scopeResolver->keywordOf($value);
        if ($keyword === 'public') {
            return null;
        }

        if ($keyword === 'namespace') {
            return $declaringNamespace === ''
                ? 'the declaration is in the global namespace, so "namespace" covers every namespace instead of narrowing anything'
                : null;
        }

        if ($keyword === 'parent') {
            return $this->parentProblem($declaringNamespace);
        }

        if ($keyword === 'root') {
            return $this->lineage->rootOf($declaringNamespace) === null
                ? 'the declaration is in the global namespace, which has no root namespace to open up'
                : null;
        }

        return $this->namespaceProblem($value);
    }

    /**
     * Returns why "parent" cannot be honoured in the given namespace, or null when it can.
     */
    public function parentProblem(string $declaringNamespace): ?string
    {
        $parent = $this->lineage->parentOf($declaringNamespace);
        if ($parent === null) {
            return 'the declaration is in the global namespace, which has no parent namespace to open up';
        }

        return $parent === ''
            ? sprintf('the parent of namespace "%s" is the global namespace, which narrows nothing; write "@visibility namespace" or name an outer namespace', $declaringNamespace)
            : null;
    }

    /**
     * Returns why a written namespace cannot be honoured, or null when it can.
     */
    public function namespaceProblem(string $value): ?string
    {
        if (!$this->scopeResolver->isNamespaceName(ltrim($value, '\\'))) {
            return 'the scope has to be "public", "root", "parent", "namespace", or a namespace name such as "App\\Domain"';
        }

        return $this->scopeResolver->isKeywordShape($value)
            ? sprintf('one bare lowercase word is read as a scope keyword, and "%s" is not one of "public", "root", "parent", "namespace"; write the keyword you meant, or write "\\%s" to name the namespace', $value, $value)
            : null;
    }
}
