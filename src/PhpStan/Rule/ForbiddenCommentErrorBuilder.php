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
    /**
     * Creates an error builder from comment formatting and pattern handling.
     */
    public function __construct(
        private readonly CommentTextFormatter $commentTextFormatter = new CommentTextFormatter(),
        private readonly ForbiddenCommentPattern $forbiddenCommentPattern = new ForbiddenCommentPattern(),
    ) {
    }

    /**
     * Builds an error for a PHPStan ignore directive.
     */
    public function phpstanIgnore(string $text, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'phpstan-ignore comments are prohibited: "%s". Remove this comment and re-run PHPStan to reveal the actual error it was suppressing, then fix the root cause. If the error is a false positive, ask a human operator to add an ignoreErrors entry in phpstan.neon with the error\'s identifier.',
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
                'infection-ignore-all comments are prohibited: "%s". Remove this comment and run mutation testing to identify surviving mutants, then strengthen assertions or add test cases to kill them. If the code is genuinely untestable, restructure it to improve testability.',
                $this->commentTextFormatter->truncate($text)
            )
        )
            ->identifier('customRules.infectionIgnoreAllComment')
            ->line($line)
            ->build();
    }
}
