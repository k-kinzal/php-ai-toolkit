<?php

declare(strict_types=1);

namespace PhpAiToolkit\LocGuard\Analysis;

use function array_key_exists;
use function array_pop;
use function count;

use PhpToken;

/**
 * Tracks class and function brace context while scanning function metrics.
 */
final class FunctionScanState
{
    /** @var list<string> */
    private array $braceStack = [];

    /** @var list<string> */
    private array $classNames = [];

    /** @var array<int, string> */
    private array $classBodyStarts = [];

    /** @var array<int, bool> */
    private array $functionBodyStarts = [];

    /**
     * Registers the opening brace index for a class-like body.
     */
    public function registerClassBody(int $bodyStart, string $className): void
    {
        $this->classBodyStarts[$bodyStart] = $className;
    }

    /**
     * Registers the opening token index for a function-like body.
     */
    public function registerFunctionBody(int $bodyStart): void
    {
        $this->functionBodyStarts[$bodyStart] = true;
    }

    /**
     * Reports whether the scanner is currently inside a class-like body.
     */
    public function isInClass(): bool
    {
        return ($this->braceStack[count($this->braceStack) - 1] ?? null) === 'class';
    }

    /**
     * Returns the current class-like name when scanning inside a class body.
     */
    public function currentClassName(): ?string
    {
        return $this->classNames[count($this->classNames) - 1] ?? null;
    }

    /**
     * Advances the brace context using the current token.
     */
    public function advance(PhpToken $token, int $index): void
    {
        if ($token->text === '{') {
            if (array_key_exists($index, $this->classBodyStarts)) {
                $this->braceStack[] = 'class';
                $this->classNames[] = $this->classBodyStarts[$index];
            } elseif (array_key_exists($index, $this->functionBodyStarts)) {
                $this->braceStack[] = 'function';
            } else {
                $this->braceStack[] = 'block';
            }
        } elseif ($token->text === '}') {
            $context = array_pop($this->braceStack);
            if ($context === 'class') {
                array_pop($this->classNames);
            }
        }
    }
}
