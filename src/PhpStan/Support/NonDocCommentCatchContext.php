<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Support;

/**
 * Tracks whether the current token position is inside a catch body.
 */
final class NonDocCommentCatchContext
{
    private int $braceDepth = 0;

    private bool $pendingCatch = false;

    /** @var list<int> */
    private array $catchBodyBraceDepths = [];

    /**
     * Marks a catch token whose body opening brace is expected later.
     */
    public function registerCatch(): void
    {
        $this->pendingCatch = true;
    }

    /**
     * Cancels a pending catch body when a conflicting token is seen.
     */
    public function cancelPendingCatch(): void
    {
        $this->pendingCatch = false;
    }

    /**
     * Registers a single-character token that may open or close a catch body.
     */
    public function registerStringToken(string $token): void
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

    /**
     * Reports whether line comments are allowed by catch-body context.
     */
    public function allowsLineComment(): bool
    {
        return $this->catchBodyBraceDepths !== [];
    }
}
