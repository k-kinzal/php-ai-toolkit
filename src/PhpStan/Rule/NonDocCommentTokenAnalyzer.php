<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Support\NonDocCommentContext;
use PHPStan\Rules\IdentifierRuleError;

/**
 * Analyses tokenizer comments for non-PHPDoc comments.
 */
final class NonDocCommentTokenAnalyzer
{
    /**
     * Creates an analyzer from suppression detection and error building.
     */
    public function __construct(
        private readonly ForbiddenCommentPattern $forbiddenCommentPattern = new ForbiddenCommentPattern(),
        private readonly NonDocCommentErrorBuilder $errorBuilder = new NonDocCommentErrorBuilder(),
    ) {
    }

    /**
     * Returns non-PHPDoc comment errors found in tokens.
     *
     * @param array<int, array{int, string, int}|string> $tokens
     * @return list<IdentifierRuleError>
     */
    public function errors(array $tokens): array
    {
        $errors = [];
        $context = new NonDocCommentContext();

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                $context->registerStringToken($token);
                continue;
            }

            [$tokenType, $text, $line] = $token;

            if ($tokenType !== T_COMMENT) {
                $context->registerToken($tokenType, $text);
                continue;
            }

            if ($this->forbiddenCommentPattern->isHandled($text)) {
                continue;
            }

            if (str_starts_with($text, '//') && $context->allowsLineComment()) {
                continue;
            }

            $errors[] = $this->errorBuilder->error($text, $line);
        }

        return $errors;
    }
}
