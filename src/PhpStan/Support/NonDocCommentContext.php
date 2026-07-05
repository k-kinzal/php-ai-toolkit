<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Support;

/**
 * Tracks token context where // comments are allowed by ForbidNonDocCommentRule.
 */
final class NonDocCommentContext
{
    /**
     * Creates a context from catch and array context trackers.
     */
    public function __construct(
        private readonly NonDocCommentCatchContext $catchContext = new NonDocCommentCatchContext(),
        private readonly NonDocCommentArrayContext $arrayContext = new NonDocCommentArrayContext(),
    ) {
    }

    /**
     * Registers a single-character token such as a delimiter.
     */
    public function registerStringToken(string $token): void
    {
        $this->catchContext->registerStringToken($token);
        $this->arrayContext->registerStringToken($token);
    }

    /**
     * Registers a tokenizer array token that is not a comment.
     */
    public function registerToken(int $tokenType, string $text): void
    {
        if ($tokenType === T_CATCH) {
            $this->catchContext->registerCatch();
            $this->arrayContext->registerToken($tokenType, $text);

            return;
        }

        if ($tokenType === T_ARRAY) {
            $this->catchContext->cancelPendingCatch();
            $this->arrayContext->registerArrayToken($text);

            return;
        }

        $this->arrayContext->registerToken($tokenType, $text);
    }

    /**
     * Determines whether a // comment is allowed at the current token position.
     */
    public function allowsLineComment(): bool
    {
        return $this->catchContext->allowsLineComment() || $this->arrayContext->allowsLineComment();
    }
}
