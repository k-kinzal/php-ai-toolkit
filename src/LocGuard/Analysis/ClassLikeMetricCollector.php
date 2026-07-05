<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function count;

use PhpToken;

/**
 * Collects class-like declaration metrics from tokenized PHP source.
 */
final class ClassLikeMetricCollector
{
    /** @readonly */
    private ClassLikeDeclarationReader $declarationReader;

    /** @readonly */
    private PhpTokenNavigator $tokenNavigator;

    /**
     * Creates a collector from declaration reading and token navigation collaborators.
     */
    public function __construct(
        ?ClassLikeDeclarationReader $declarationReader = null,
        ?PhpTokenNavigator $tokenNavigator = null,
    ) {
        $this->declarationReader = $declarationReader ?? new ClassLikeDeclarationReader();
        $this->tokenNavigator = $tokenNavigator ?? new PhpTokenNavigator();
    }

    /**
     * Collects line-count metrics for class, trait, interface, and enum declarations.
     *
     * @param list<PhpToken> $tokens
     * @return list<ClassLikeMetric>
     */
    public function collect(array $tokens): array
    {
        $metrics = [];

        foreach ($tokens as $index => $token) {
            if (!$this->declarationReader->isDeclaration($tokens, $index)) {
                continue;
            }

            $bodyStart = $this->tokenNavigator->nextText($tokens, $index, '{');
            $bodyEnd = $bodyStart === null ? null : $this->tokenNavigator->matchingBrace($tokens, $bodyStart);
            if ($bodyEnd === null) {
                continue;
            }

            $metrics[] = new ClassLikeMetric(
                $this->declarationReader->kind($token),
                $this->declarationReader->name($tokens, $index),
                $token->line,
                $tokens[$bodyEnd]->line,
            );
        }

        return $metrics;
    }
}
