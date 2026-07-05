<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Support;

/**
 * Tracks whether the current token position is inside an array literal.
 */
final class NonDocCommentArrayContext
{
    private int $bracketDepth = 0;

    private int $parenDepth = 0;

    private bool $pendingLongArray = false;

    /** @var list<int> */
    private array $shortArrayBracketDepths = [];

    /** @var list<int> */
    private array $longArrayParenDepths = [];

    /** @var array{int, string}|string|null */
    private array|string|null $previousSignificantToken = null;

    /** @readonly */
    private ShortArrayOpeningPolicy $shortArrayOpeningPolicy;

    /** @readonly */
    private NonDocCommentTokenClassifier $tokenClassifier;

    /**
     * Creates an array context from short-array opening policy and token classification.
     */
    public function __construct(
        ?ShortArrayOpeningPolicy $shortArrayOpeningPolicy = null,
        ?NonDocCommentTokenClassifier $tokenClassifier = null,
    ) {
        $this->shortArrayOpeningPolicy = $shortArrayOpeningPolicy ?? new ShortArrayOpeningPolicy();
        $this->tokenClassifier = $tokenClassifier ?? new NonDocCommentTokenClassifier();
    }

    /**
     * Registers a long-array token.
     */
    public function registerArrayToken(string $text): void
    {
        $this->pendingLongArray = true;
        $this->previousSignificantToken = [T_ARRAY, $text];
    }

    /**
     * Registers a tokenizer token that is not T_ARRAY.
     */
    public function registerToken(int $tokenType, string $text): void
    {
        if (!$this->tokenClassifier->isSignificant($tokenType)) {
            return;
        }

        $this->pendingLongArray = false;
        $this->previousSignificantToken = [$tokenType, $text];
    }

    /**
     * Registers a delimiter or other single-character token.
     */
    public function registerStringToken(string $token): void
    {
        if ($token === '[') {
            $this->registerOpeningBracket();

            return;
        }

        if ($token === ']') {
            $this->registerClosingBracket();

            return;
        }

        if ($token === '(') {
            $this->registerOpeningParenthesis();

            return;
        }

        if ($token === ')') {
            $this->registerClosingParenthesis();

            return;
        }

        $this->registerOtherStringToken($token);
    }

    /**
     * Registers an opening square bracket.
     */
    public function registerOpeningBracket(): void
    {
        ++$this->bracketDepth;
        if ($this->shortArrayOpeningPolicy->isOpening($this->previousSignificantToken)) {
            $this->shortArrayBracketDepths[] = $this->bracketDepth;
        }
        $this->pendingLongArray = false;
        $this->previousSignificantToken = '[';
    }

    /**
     * Registers a closing square bracket.
     */
    public function registerClosingBracket(): void
    {
        $lastShortArrayDepth = $this->shortArrayBracketDepths[count($this->shortArrayBracketDepths) - 1] ?? null;
        if ($lastShortArrayDepth === $this->bracketDepth) {
            array_pop($this->shortArrayBracketDepths);
        }
        if ($this->bracketDepth > 0) {
            --$this->bracketDepth;
        }
        $this->pendingLongArray = false;
        $this->previousSignificantToken = ']';
    }

    /**
     * Registers an opening parenthesis.
     */
    public function registerOpeningParenthesis(): void
    {
        ++$this->parenDepth;
        if ($this->pendingLongArray) {
            $this->longArrayParenDepths[] = $this->parenDepth;
        }
        $this->pendingLongArray = false;
        $this->previousSignificantToken = '(';
    }

    /**
     * Registers a closing parenthesis.
     */
    public function registerClosingParenthesis(): void
    {
        $lastLongArrayDepth = $this->longArrayParenDepths[count($this->longArrayParenDepths) - 1] ?? null;
        if ($lastLongArrayDepth === $this->parenDepth) {
            array_pop($this->longArrayParenDepths);
        }
        if ($this->parenDepth > 0) {
            --$this->parenDepth;
        }
        $this->pendingLongArray = false;
        $this->previousSignificantToken = ')';
    }

    /**
     * Registers a non-delimiter string token.
     */
    public function registerOtherStringToken(string $token): void
    {
        $this->pendingLongArray = false;
        $this->previousSignificantToken = $token;
    }

    /**
     * Reports whether line comments are allowed by array-literal context.
     */
    public function allowsLineComment(): bool
    {
        return $this->shortArrayBracketDepths !== [] || $this->longArrayParenDepths !== [];
    }
}
