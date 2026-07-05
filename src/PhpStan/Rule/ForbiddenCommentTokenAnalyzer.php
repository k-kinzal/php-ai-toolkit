<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;

/**
 * Analyses tokenizer comments for prohibited suppression directives.
 */
final class ForbiddenCommentTokenAnalyzer
{
    /**
     * Creates an analyzer from pattern detection and error building.
     */
    public function __construct(
        private readonly ForbiddenCommentPattern $forbiddenCommentPattern = new ForbiddenCommentPattern(),
        private readonly ForbiddenCommentErrorBuilder $errorBuilder = new ForbiddenCommentErrorBuilder(),
    ) {
    }

    /**
     * Returns errors for prohibited comments found in tokens.
     *
     * @param array<int, array{int, string, int}|string> $tokens
     * @return list<IdentifierRuleError>
     */
    public function errors(array $tokens): array
    {
        $errors = [];

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            [$tokenType, $text, $line] = $token;

            if (!in_array($tokenType, [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if ($this->forbiddenCommentPattern->isPhpstanIgnore($text)) {
                $errors[] = $this->errorBuilder->phpstanIgnore($text, $line);
            }

            if ($this->forbiddenCommentPattern->isInfectionIgnoreAll($text)) {
                $errors[] = $this->errorBuilder->infectionIgnoreAll($text, $line);
            }
        }

        return $errors;
    }
}
