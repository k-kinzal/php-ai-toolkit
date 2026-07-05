<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Builds errors for single-line PHPDoc comments.
 */
final class SingleLinePhpDocErrorBuilder
{
    /**
     * Creates an error builder from comment text formatting.
     */
    public function __construct(
        private readonly CommentTextFormatter $commentTextFormatter = new CommentTextFormatter(),
    ) {
    }

    /**
     * Builds a rule error for a single-line PHPDoc comment.
     */
    public function error(string $text, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Single-line PHPDoc is prohibited: "%s". Rewrite as a multi-line PHPDoc block: open with /** on its own line, write the description on the next line prefixed with " * ", and close with */ on its own line.',
                $this->commentTextFormatter->truncate($text)
            )
        )
            ->identifier('customRules.singleLinePhpDoc')
            ->line($line)
            ->build();
    }
}
