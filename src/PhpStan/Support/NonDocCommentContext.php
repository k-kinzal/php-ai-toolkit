<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Support;

/**
 * Tracks token context where // comments are allowed by ForbidNonDocCommentRule.
 */
final class NonDocCommentContext
{
    private int $braceDepth = 0;

    private int $bracketDepth = 0;

    private int $parenDepth = 0;

    private bool $pendingCatch = false;

    private bool $pendingLongArray = false;

    /** @var list<int> */
    private array $catchBodyBraceDepths = [];

    /** @var list<int> */
    private array $shortArrayBracketDepths = [];

    /** @var list<int> */
    private array $longArrayParenDepths = [];

    /** @var array{int, string}|string|null */
    private array|string|null $previousSignificantToken = null;

    /**
     * Registers a single-character token such as a delimiter.
     */
    public function registerStringToken(string $token): void
    {
        $this->updateCatchBraceState($token);
        $this->updateArrayDelimiterState($token);
    }

    /**
     * Registers a tokenizer array token that is not a comment.
     */
    public function registerToken(int $tokenType, string $text): void
    {
        if ($tokenType === T_CATCH) {
            $this->pendingCatch = true;
            $this->pendingLongArray = false;
            $this->previousSignificantToken = [$tokenType, $text];

            return;
        }

        if ($tokenType === T_ARRAY) {
            $this->pendingCatch = false;
            $this->pendingLongArray = true;
            $this->previousSignificantToken = [$tokenType, $text];

            return;
        }

        if (!$this->isSignificantToken($tokenType)) {
            return;
        }

        $this->pendingLongArray = false;
        $this->previousSignificantToken = [$tokenType, $text];
    }

    /**
     * Determines whether a // comment is allowed at the current token position.
     */
    public function allowsLineComment(): bool
    {
        return $this->catchBodyBraceDepths !== []
            || $this->shortArrayBracketDepths !== []
            || $this->longArrayParenDepths !== [];
    }

    private function updateCatchBraceState(string $token): void
    {
        if ($token === '{') {
            ++$this->braceDepth;

            if ($this->pendingCatch) {
                $this->catchBodyBraceDepths[] = $this->braceDepth;
                $this->pendingCatch = false;
            }
        }

        if ($token !== '}') {
            return;
        }

        $lastCatchBodyDepth = $this->catchBodyBraceDepths[count($this->catchBodyBraceDepths) - 1] ?? null;
        if ($lastCatchBodyDepth === $this->braceDepth) {
            array_pop($this->catchBodyBraceDepths);
        }

        if ($this->braceDepth > 0) {
            --$this->braceDepth;
        }
    }

    private function updateArrayDelimiterState(string $token): void
    {
        if ($token === '[') {
            $this->openBracketDelimiter();
            $this->registerStringDelimiter($token);

            return;
        }

        if ($token === ']') {
            $this->closeBracketDelimiter();
            $this->registerStringDelimiter($token);

            return;
        }

        if ($token === '(') {
            $this->openParenDelimiter();
            $this->registerStringDelimiter($token);

            return;
        }

        if ($token === ')') {
            $this->closeParenDelimiter();
            $this->registerStringDelimiter($token);

            return;
        }

        $this->registerStringDelimiter($token);
    }

    private function openBracketDelimiter(): void
    {
        ++$this->bracketDepth;

        if ($this->isShortArrayOpening()) {
            $this->shortArrayBracketDepths[] = $this->bracketDepth;
        }
    }

    private function closeBracketDelimiter(): void
    {
        $lastShortArrayDepth = $this->shortArrayBracketDepths[count($this->shortArrayBracketDepths) - 1] ?? null;
        if ($lastShortArrayDepth === $this->bracketDepth) {
            array_pop($this->shortArrayBracketDepths);
        }

        if ($this->bracketDepth > 0) {
            --$this->bracketDepth;
        }
    }

    private function openParenDelimiter(): void
    {
        ++$this->parenDepth;

        if ($this->pendingLongArray) {
            $this->longArrayParenDepths[] = $this->parenDepth;
        }
    }

    private function closeParenDelimiter(): void
    {
        $lastLongArrayDepth = $this->longArrayParenDepths[count($this->longArrayParenDepths) - 1] ?? null;
        if ($lastLongArrayDepth === $this->parenDepth) {
            array_pop($this->longArrayParenDepths);
        }

        if ($this->parenDepth > 0) {
            --$this->parenDepth;
        }
    }

    private function registerStringDelimiter(string $token): void
    {
        $this->pendingLongArray = false;
        $this->previousSignificantToken = $token;
    }

    private function isSignificantToken(int $tokenType): bool
    {
        return !in_array($tokenType, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG, T_CLOSE_TAG], true);
    }

    private function isShortArrayOpening(): bool
    {
        if ($this->previousSignificantToken === null || is_string($this->previousSignificantToken)) {
            return $this->previousSignificantToken !== ']' && $this->previousSignificantToken !== ')';
        }

        return !$this->tokenCanEndExpression($this->previousSignificantToken[0]);
    }

    private function tokenCanEndExpression(int $tokenType): bool
    {
        return in_array(
            $tokenType,
            [
                T_ARRAY,
                T_CLASS,
                T_CONSTANT_ENCAPSED_STRING,
                T_DNUMBER,
                T_DIR,
                T_FILE,
                T_FUNC_C,
                T_LINE,
                T_LNUMBER,
                T_METHOD_C,
                T_NAME_FULLY_QUALIFIED,
                T_NAME_QUALIFIED,
                T_NAME_RELATIVE,
                T_NS_C,
                T_STRING,
                T_TRAIT_C,
                T_VARIABLE,
            ],
            true
        );
    }
}
