<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Visibility;

use function array_merge;
use function sprintf;

use Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage;
use Toolkit\PhpStan\Rule\Visibility\Scope\ScopeProblemReader;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScope;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScopeResolver;

use function ucfirst;

/**
 * Applies @visibility declarations to the references PHPStan collected.
 */
final class VisibilityInspector
{
    /**
     * Identifier for an unusable or contradictory @visibility declaration.
     */
    public const INVALID_SCOPE_IDENTIFIER = 'customRules.visibilityInvalidScope';

    /**
     * Identifier for a reference made outside its declaration's scope.
     */
    public const OUT_OF_SCOPE_IDENTIFIER = 'customRules.visibilityOutOfScope';

    /** @readonly */
    private VisibilityScopeResolver $scopeResolver;

    /** @readonly */
    private ScopeProblemReader $problemReader;

    /** @readonly */
    private NamespaceLineage $lineage;

    /**
     * @param list<string> $exemptNamespacePrefixes namespace subtrees allowed to cross every scope
     */
    public function __construct(
        /** @readonly */
        private array $exemptNamespacePrefixes = ['Tests'],
        ?VisibilityScopeResolver $scopeResolver = null,
        ?ScopeProblemReader $problemReader = null,
        ?NamespaceLineage $lineage = null,
    ) {
        $this->scopeResolver = $scopeResolver ?? new VisibilityScopeResolver();
        $this->problemReader = $problemReader ?? new ScopeProblemReader($this->scopeResolver);
        $this->lineage = $lineage ?? new NamespaceLineage();
    }

    /**
     * Returns every invalid tag and out-of-scope reference.
     *
     * @param list<array{className: string, memberName: ?string, kind: string, namespace: string, line: int, file: string}> $references
     * @return list<array{file: string, line: int, identifier: string, symbol: string, message: string}>
     */
    public function violations(VisibilityDeclarationIndex $index, array $references): array
    {
        $violations = [];
        foreach ($index->declarations() as $declaration) {
            $violations = array_merge($violations, $this->declarationViolations($declaration));
        }

        foreach ($references as $reference) {
            if ($this->isExempt($reference['namespace'])) {
                continue;
            }

            $violation = $this->referenceViolation($index, $reference);
            if ($violation !== null) {
                $violations[] = $violation;
            }
        }

        return $violations;
    }

    /**
     * Returns every unusable tag on one declaration.
     *
     * @param array{className: string, memberName: ?string, symbol: string, kind: string, namespace: string, docComment: ?string, line: int, file: string} $declaration
     * @return list<array{file: string, line: int, identifier: string, symbol: string, message: string}>
     */
    public function declarationViolations(array $declaration): array
    {
        $scope = $this->scopeOf($declaration);
        $values = $scope->declaredValues();
        if ($values === []) {
            return [];
        }

        $violations = [];
        $hasPublic = false;
        $hasNarrowing = false;
        foreach ($values as $value) {
            if ($this->scopeResolver->keywordOf($value) === 'public') {
                $hasPublic = true;
                continue;
            }

            $hasNarrowing = true;
            $problem = $this->problemReader->problem($value, $declaration['namespace']);
            if ($problem !== null) {
                $violations[] = $this->invalidScope($declaration, $value, $problem);
            }
        }

        if ($hasPublic && $hasNarrowing) {
            $violations[] = $this->contradictoryScopes($declaration);
        }

        return $violations;
    }

    /**
     * Returns the violation for one reference, or null when it is admitted.
     *
     * @param array{className: string, memberName: ?string, kind: string, namespace: string, line: int, file: string} $reference
     * @return array{file: string, line: int, identifier: string, symbol: string, message: string}|null
     */
    public function referenceViolation(VisibilityDeclarationIndex $index, array $reference): ?array
    {
        $member = $reference['memberName'] === null
            ? null
            : $index->memberDeclaration($reference['className'], $reference['memberName']);
        if ($member !== null && !$this->scopeOf($member)->permits($reference['namespace'])) {
            return $this->outOfScope($member, $reference);
        }

        $class = $index->classDeclaration($reference['className']);
        if ($class === null || $this->scopeOf($class)->permits($reference['namespace'])) {
            return null;
        }

        return $this->outOfScope($class, $reference);
    }

    /**
     * Reports whether a namespace is exempt from reference checks.
     */
    public function isExempt(string $namespace): bool
    {
        foreach ($this->exemptNamespacePrefixes as $prefix) {
            if ($prefix !== '' && $this->lineage->covers($prefix, $namespace)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolves the @visibility scope of a collected declaration.
     *
     * @param array{namespace: string, docComment: ?string} $declaration
     */
    public function scopeOf(array $declaration): VisibilityScope
    {
        return $this->scopeResolver->resolve($declaration['docComment'], $declaration['namespace']);
    }

    /**
     * Builds an error for a reference outside the declaration scope.
     *
     * @param array{symbol: string, kind: string, namespace: string, docComment: ?string} $declaration
     * @param array{kind: string, namespace: string, line: int, file: string} $reference
     * @return array{file: string, line: int, identifier: string, symbol: string, message: string}
     */
    public function outOfScope(array $declaration, array $reference): array
    {
        $scope = $this->scopeOf($declaration);

        return [
            'file' => $reference['file'],
            'line' => $reference['line'],
            'identifier' => self::OUT_OF_SCOPE_IDENTIFIER,
            'symbol' => $declaration['symbol'],
            'message' => sprintf(
                '%s %s is not visible from %s: the declaration is marked %s, so it may only be named from %s. Move this %s into that namespace, or widen the declaration to "@visibility %s".',
                ucfirst($declaration['kind']),
                $declaration['symbol'],
                $this->describeNamespace($reference['namespace']),
                $scope->describeTags(),
                $scope->describeAllowed(),
                $reference['kind'],
                $this->wideningFor($declaration['namespace'], $reference['namespace']),
            ),
        ];
    }

    /**
     * Builds an error for an unusable scope value.
     *
     * @param array{file: string, line: int, symbol: string, kind: string} $declaration
     * @return array{file: string, line: int, identifier: string, symbol: string, message: string}
     */
    public function invalidScope(array $declaration, string $value, string $reason): array
    {
        return [
            'file' => $declaration['file'],
            'line' => $declaration['line'],
            'identifier' => self::INVALID_SCOPE_IDENTIFIER,
            'symbol' => $declaration['symbol'],
            'message' => sprintf('Fix "@visibility %s" on %s %s: %s.', $value, $declaration['kind'], $declaration['symbol'], $reason),
        ];
    }

    /**
     * Builds an error for public combined with a narrowing tag.
     *
     * @param array{file: string, line: int, symbol: string, kind: string} $declaration
     * @return array{file: string, line: int, identifier: string, symbol: string, message: string}
     */
    public function contradictoryScopes(array $declaration): array
    {
        return [
            'file' => $declaration['file'],
            'line' => $declaration['line'],
            'identifier' => self::INVALID_SCOPE_IDENTIFIER,
            'symbol' => $declaration['symbol'],
            'message' => sprintf(
                'Remove either "@visibility public" or the narrowing @visibility tags on %s %s: "public" makes the declaration visible everywhere, so keeping both leaves the narrower tags with no effect.',
                $declaration['kind'],
                $declaration['symbol'],
            ),
        ];
    }

    /**
     * Phrases a namespace, naming the global namespace explicitly.
     */
    public function describeNamespace(string $namespace): string
    {
        return $namespace === '' ? 'the global namespace' : sprintf('namespace "%s"', $namespace);
    }

    /**
     * Returns the narrowest scope value that would admit a reference.
     */
    public function wideningFor(string $declaringNamespace, string $referencingNamespace): string
    {
        $ancestor = $this->lineage->commonAncestorOf($declaringNamespace, $referencingNamespace);

        return $ancestor === '' ? 'public' : $ancestor;
    }
}
