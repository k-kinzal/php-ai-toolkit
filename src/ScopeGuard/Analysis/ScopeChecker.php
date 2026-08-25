<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Analysis;

use function array_merge;

use Toolkit\ScopeGuard\Analysis\Declaration\Declaration;
use Toolkit\ScopeGuard\Analysis\Reference\Reference;
use Toolkit\ScopeGuard\Analysis\Scope\ExemptNamespaces;
use Toolkit\ScopeGuard\Analysis\Scope\ScopeProblemReader;
use Toolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver;

/**
 * Checks a scanned project against the scopes its declarations carry.
 */
final class ScopeChecker
{
    /** @readonly */
    private ScopeProblemReader $problemReader;

    /** @readonly */
    private VisibilityScopeResolver $scopeResolver;

    /** @readonly */
    private ScopeViolationBuilder $violationBuilder;

    /**
     * Creates the checker from tag diagnosis, scope resolution, and violation building.
     */
    public function __construct(
        ?ScopeProblemReader $problemReader = null,
        ?VisibilityScopeResolver $scopeResolver = null,
        ?ScopeViolationBuilder $violationBuilder = null,
    ) {
        $this->problemReader = $problemReader ?? new ScopeProblemReader();
        $this->scopeResolver = $scopeResolver ?? new VisibilityScopeResolver();
        $this->violationBuilder = $violationBuilder ?? new ScopeViolationBuilder();
    }

    /**
     * Returns every violation of the scanned project.
     *
     * @return list<Violation>
     */
    public function violations(ProjectScan $scan, ExemptNamespaces $exemptNamespaces): array
    {
        return array_merge($this->scopeDeclarationViolations($scan), $this->referenceViolations($scan, $exemptNamespaces));
    }

    /**
     * Returns the violations of every @visibility tag that cannot be honoured.
     *
     * A tag is wrong wherever it is written, so exempt namespaces are checked too.
     *
     * @return list<Violation>
     */
    public function scopeDeclarationViolations(ProjectScan $scan): array
    {
        $violations = [];
        foreach ($scan->index->declarations() as $declaration) {
            $violations = array_merge($violations, $this->declarationViolations($declaration));
        }

        return $violations;
    }

    /**
     * Returns the tag violations of one declaration.
     *
     * @return list<Violation>
     */
    public function declarationViolations(Declaration $declaration): array
    {
        $values = $declaration->scope->declaredValues;
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
            $problem = $this->problemReader->problem($value, $declaration->namespace);
            if ($problem !== null) {
                $violations[] = $this->violationBuilder->invalidScope($declaration, $value, $problem);
            }
        }

        if ($hasPublic && $hasNarrowing) {
            $violations[] = $this->violationBuilder->contradictoryScopes($declaration);
        }

        return $violations;
    }

    /**
     * Returns the violations of every reference that leaves the scope it names.
     *
     * @return list<Violation>
     */
    public function referenceViolations(ProjectScan $scan, ExemptNamespaces $exemptNamespaces): array
    {
        $violations = [];
        foreach ($scan->references as $reference) {
            if ($exemptNamespaces->contains($reference->namespace)) {
                continue;
            }

            $violation = $this->referenceViolation($scan, $reference);
            if ($violation !== null) {
                $violations[] = $violation;
            }
        }

        return $violations;
    }

    /**
     * Returns the violation of one reference, or null when the reference is in scope.
     *
     * A member is never wider than the class-like that declares it, so a reference the
     * member allows is still checked against the class it belongs to.
     */
    public function referenceViolation(ProjectScan $scan, Reference $reference): ?Violation
    {
        $member = $reference->memberName === null ? null : $scan->index->memberDeclaration($reference->className, $reference->memberName);
        if ($member !== null && !$member->scope->permits($reference->namespace)) {
            return $this->violationBuilder->outOfScope($member, $reference);
        }

        $class = $scan->index->classDeclaration($reference->className);

        return $class === null || $class->scope->permits($reference->namespace)
            ? null
            : $this->violationBuilder->outOfScope($class, $reference);
    }
}
