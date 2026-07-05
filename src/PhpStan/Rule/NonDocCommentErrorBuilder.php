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
                'Remove comment "%s" or convert it to /** ... */ PHPDoc. Only // comments inside catch blocks or array literals are allowed.',
                $this->commentTextFormatter->truncate($text)
            )
        )
            ->identifier('customRules.nonDocComment')
            ->line($line)
            ->build();
    }
}
