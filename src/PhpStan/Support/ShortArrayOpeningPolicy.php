<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Support;

/**
 * Decides whether a bracket opens a short array literal.
 */
final class ShortArrayOpeningPolicy
{
    /** @readonly */
    private NonDocCommentTokenClassifier $tokenClassifier;

    /**
     * Creates a policy from token classification.
     */
    public function __construct(
        ?NonDocCommentTokenClassifier $tokenClassifier = null,
    ) {
        $this->tokenClassifier = $tokenClassifier ?? new NonDocCommentTokenClassifier();
    }

    /**
     * Reports whether the previous significant token allows a short array opening.
     *
     * @param array{int, string}|string|null $previousSignificantToken
     */
    public function isOpening(array|string|null $previousSignificantToken): bool
    {
        if ($previousSignificantToken === null || is_string($previousSignificantToken)) {
            return $previousSignificantToken !== ']' && $previousSignificantToken !== ')';
        }

        return !$this->tokenClassifier->canEndExpression($previousSignificantToken[0]);
    }
}
