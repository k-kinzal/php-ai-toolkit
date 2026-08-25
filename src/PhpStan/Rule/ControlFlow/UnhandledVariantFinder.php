<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ControlFlow;

use function count;

use PHPStan\Analyser\Scope;
use PHPStan\Type\Type;

/**
 * Names the values of a closed type that no branch of a dispatch claims.
 *
 * The analyzer already knows how to take a value out of a type once a condition on it is
 * false, so the branches are replayed as false one after another and whatever the subject
 * can still be afterwards is what the dispatch left out.
 */
final class UnhandledVariantFinder
{
    /**
     * Returns the values the branches leave unclaimed, in the order the type lists them.
     *
     * A dispatch that claims none of the values is reported as claiming all of them: it is
     * either a comparison that happens to sit on a closed type, or a subject the analyzer
     * cannot narrow, and neither is a half-finished dispatch worth pointing at.
     *
     * @param list<\PhpParser\Node\Expr> $branchNarrowings a condition per branch, true when that branch runs
     * @param list<Type> $variants
     * @return list<Type>
     */
    public function find(Scope $scope, \PhpParser\Node\Expr $subject, array $branchNarrowings, array $variants): array
    {
        $remainingScope = $scope;
        foreach ($branchNarrowings as $branchNarrowing) {
            $remainingScope = $remainingScope->filterByFalseyValue($branchNarrowing);
        }

        $remainingType = $remainingScope->getType($subject);
        $unhandled = [];
        foreach ($variants as $variant) {
            if ($remainingType->isSuperTypeOf($variant)->no()) {
                continue;
            }

            $unhandled[] = $variant;
        }

        if (count($unhandled) === count($variants)) {
            return [];
        }

        return $unhandled;
    }
}
