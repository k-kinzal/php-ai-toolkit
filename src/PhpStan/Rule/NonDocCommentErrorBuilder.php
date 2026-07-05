<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Builds errors for non-PHPDoc comments.
 */
final class NonDocCommentErrorBuilder
{
    /** @readonly */
    private CommentTextFormatter $commentTextFormatter;

    /**
     * Creates an error builder from comment formatting.
     */
    public function __construct(
        ?CommentTextFormatter $commentTextFormatter = null,
    ) {
        $this->commentTextFormatter = $commentTextFormatter ?? new CommentTextFormatter();
    }

    /**
     * Builds an error for one non-PHPDoc comment.
     */
    public function error(string $text, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Non-PHPDoc comment is prohibited: "%s". Only /** ... */ PHPDoc blocks are allowed, except // comments inside catch blocks or array literals. Remove this comment or convert to a PHPDoc block if it documents an API contract.',
                $this->commentTextFormatter->truncate($text)
            )
        )
            ->identifier('customRules.nonDocComment')
            ->line($line)
            ->build();
    }
}
