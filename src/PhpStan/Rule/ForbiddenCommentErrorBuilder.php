<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Builds errors for prohibited suppression comments.
 */
final class ForbiddenCommentErrorBuilder
{
    /** @readonly */
    private CommentTextFormatter $commentTextFormatter;

    /** @readonly */
    private ForbiddenCommentPattern $forbiddenCommentPattern;

    /**
     * Creates an error builder from comment formatting and pattern handling.
     */
    public function __construct(
        ?CommentTextFormatter $commentTextFormatter = null,
        ?ForbiddenCommentPattern $forbiddenCommentPattern = null,
    ) {
        $this->commentTextFormatter = $commentTextFormatter ?? new CommentTextFormatter();
        $this->forbiddenCommentPattern = $forbiddenCommentPattern ?? new ForbiddenCommentPattern();
    }

    /**
     * Builds an error for a PHPStan ignore directive.
     */
    public function phpstanIgnore(string $text, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Remove phpstan-ignore comment "%s". Re-run PHPStan and fix the revealed error. AI agents must not edit ignoreErrors; ask a human operator only when suppression is genuinely justified.',
                $this->commentTextFormatter->truncate($text)
            )
        )
            ->identifier('customRules.phpstanIgnoreComment')
            ->line($this->forbiddenCommentPattern->reportedLine($text, $line))
            ->build();
    }

    /**
     * Builds an error for an Infection ignore-all directive.
     */
    public function infectionIgnoreAll(string $text, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Remove infection-ignore-all comment "%s". Run mutation testing and strengthen assertions or add focused tests. Ask a human operator only when an exception is genuinely justified.',
                $this->commentTextFormatter->truncate($text)
            )
        )
            ->identifier('customRules.infectionIgnoreAllComment')
            ->line($line)
            ->build();
    }
}
