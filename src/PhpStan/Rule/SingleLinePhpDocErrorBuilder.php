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
    /** @readonly */
    private CommentTextFormatter $commentTextFormatter;

    /**
     * Creates an error builder from comment text formatting.
     */
    public function __construct(?CommentTextFormatter $commentTextFormatter = null)
    {
        $this->commentTextFormatter = $commentTextFormatter ?? new CommentTextFormatter();
    }

    /**
     * Builds a rule error for a single-line PHPDoc comment.
     */
    public function error(string $text, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Rewrite PHPDoc "%s" as a multi-line block with /** and */ on their own lines.',
                $this->commentTextFormatter->truncate($text)
            )
        )
            ->identifier('customRules.singleLinePhpDoc')
            ->line($line)
            ->build();
    }
}
