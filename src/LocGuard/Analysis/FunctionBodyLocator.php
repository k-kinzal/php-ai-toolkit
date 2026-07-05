<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function count;

use PhpToken;

use const T_DOUBLE_ARROW;

/**
 * Locates block and expression bodies for function-like declarations.
 */
final class FunctionBodyLocator
{
    /** @readonly */
    private PhpTokenNavigator $tokenNavigator;

    /** @readonly */
    private ArrowExpressionBoundary $arrowExpressionBoundary;

    /**
     * Creates a body locator from token navigation and arrow-expression boundary detection.
     */
    public function __construct(
        ?PhpTokenNavigator $tokenNavigator = null,
        ?ArrowExpressionBoundary $arrowExpressionBoundary = null,
    ) {
        $this->tokenNavigator = $tokenNavigator ?? new PhpTokenNavigator();
        $this->arrowExpressionBoundary = $arrowExpressionBoundary ?? new ArrowExpressionBoundary();
    }

    /**
     * Returns the opening brace index for a block-bodied function.
     *
     * @param list<PhpToken> $tokens
     */
    public function blockBodyStart(array $tokens, int $index): ?int
    {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            if ($tokens[$i]->text === '{') {
                return $i;
            }

            if ($tokens[$i]->text === ';') {
                return null;
            }
        }

        return null;
    }

    /**
     * Returns the closing brace index for a block-bodied function.
     *
     * @param list<PhpToken> $tokens
     */
    public function blockBodyEnd(array $tokens, int $bodyStart): ?int
    {
        return $this->tokenNavigator->matchingBrace($tokens, $bodyStart);
    }

    /**
     * Returns the double-arrow token index for an arrow function body.
     *
     * @param list<PhpToken> $tokens
     */
    public function arrowBodyStart(array $tokens, int $index): ?int
    {
        return $this->tokenNavigator->nextId($tokens, $index, T_DOUBLE_ARROW);
    }

    /**
     * Returns the exclusive end index for an arrow function body.
     *
     * @param list<PhpToken> $tokens
     */
    public function arrowBodyEnd(array $tokens, int $bodyStart): int
    {
        return $this->arrowExpressionBoundary->end($tokens, $bodyStart);
    }
}
