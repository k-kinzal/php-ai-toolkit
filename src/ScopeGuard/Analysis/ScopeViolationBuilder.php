<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Analysis;

use function sprintf;

use Toolkit\ScopeGuard\Analysis\Declaration\Declaration;
use Toolkit\ScopeGuard\Analysis\Reference\Reference;
use Toolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;

use function ucfirst;

/**
 * Builds the violations ScopeGuard reports.
 */
final class ScopeViolationBuilder
{
    /** @readonly */
    private NamespaceLineage $lineage;

    /**
     * Creates the builder from namespace ancestry.
     */
    public function __construct(?NamespaceLineage $lineage = null)
    {
        $this->lineage = $lineage ?? new NamespaceLineage();
    }

    /**
     * Reports a reference that names a declaration from outside its scope.
     */
    public function outOfScope(Declaration $declaration, Reference $reference): Violation
    {
        return new Violation(
            $reference->path,
            $reference->line,
            'out_of_scope',
            $declaration->symbol,
            sprintf(
                '%s %s is not visible from %s: the declaration is marked %s, so it may only be named from %s. Move this %s into that namespace, or widen the declaration to "@visibility %s".',
                ucfirst($declaration->kind),
                $declaration->symbol,
                $this->describeNamespace($reference->namespace),
                $declaration->scope->describeTags(),
                $declaration->scope->describeAllowed(),
                $reference->kind,
                $this->wideningFor($declaration->namespace, $reference->namespace)
            ),
        );
    }

    /**
     * Reports an @visibility tag that cannot be honoured as written.
     */
    public function invalidScope(Declaration $declaration, string $value, string $reason): Violation
    {
        return new Violation(
            $declaration->path,
            $declaration->line,
            'invalid_scope',
            $declaration->symbol,
            sprintf('Fix "@visibility %s" on %s %s: %s.', $value, $declaration->kind, $declaration->symbol, $reason),
        );
    }

    /**
     * Reports a declaration that combines "@visibility public" with a narrowing tag.
     */
    public function contradictoryScopes(Declaration $declaration): Violation
    {
        return new Violation(
            $declaration->path,
            $declaration->line,
            'invalid_scope',
            $declaration->symbol,
            sprintf(
                'Remove either "@visibility public" or the narrowing @visibility tags on %s %s: "public" makes the declaration visible everywhere, so keeping both leaves the narrower tags with no effect.',
                $declaration->kind,
                $declaration->symbol
            ),
        );
    }

    /**
     * Phrases a namespace for a message, naming the global namespace explicitly.
     */
    public function describeNamespace(string $namespace): string
    {
        return $namespace === '' ? 'the global namespace' : sprintf('namespace "%s"', $namespace);
    }

    /**
     * Returns the narrowest scope value that would admit the reference.
     */
    public function wideningFor(string $declaringNamespace, string $referencingNamespace): string
    {
        $ancestor = $this->lineage->commonAncestorOf($declaringNamespace, $referencingNamespace);

        return $ancestor === '' ? 'public' : $ancestor;
    }
}
