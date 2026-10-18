<?php

declare(strict_types=1);

namespace DecisionsLint\Source;

use PhpToken;

/**
 * The tokens of one PHP file, with the navigation a declaration scanner needs.
 */
final class Tokens
{
    /**
     * @var list<PhpToken>
     */
    private array $tokens;

    /**
     * Tokenizes the source text.
     */
    public function __construct(private string $code)
    {
        $this->tokens = array_values(PhpToken::tokenize($code));
    }

    /**
     * Returns the number of tokens.
     */
    public function count(): int
    {
        return count($this->tokens);
    }

    /**
     * Returns the token at an index.
     */
    public function at(int $index): PhpToken
    {
        return $this->tokens[$index];
    }

    /**
     * Returns the index of the next token after an index that is not whitespace or a comment, or null at the end.
     */
    public function nextSignificant(int $index): ?int
    {
        for ($i = $index + 1; $i < count($this->tokens); ++$i) {
            if (!$this->tokens[$i]->isIgnorable()) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Returns the index of the previous token before an index that is not whitespace or a comment, or null at the start.
     */
    public function previousSignificant(int $index): ?int
    {
        for ($i = $index - 1; $i >= 0; --$i) {
            if (!$this->tokens[$i]->isIgnorable()) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Returns the index of the closing token that balances the opening token at an index, or the last index when unbalanced.
     */
    public function closing(int $open, string $openText = '{', string $closeText = '}'): int
    {
        $depth = 0;
        for ($i = $open; $i < count($this->tokens); ++$i) {
            $text = $this->tokens[$i]->text;
            if ($text === $openText || ($openText === '{' && $this->opensBrace($this->tokens[$i]))) {
                ++$depth;
            } elseif ($text === $closeText) {
                --$depth;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return count($this->tokens) - 1;
    }

    /**
     * Tells whether a token opens a brace scope with a token other than a bare brace, such as `${` or `"{$`.
     */
    public function opensBrace(PhpToken $token): bool
    {
        return $token->is([T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES]);
    }

    /**
     * Returns the index of the first token at or after an index whose text is one of the given, or null.
     *
     * @param list<string> $texts
     */
    public function find(int $from, array $texts): ?int
    {
        for ($i = $from; $i < count($this->tokens); ++$i) {
            if (in_array($this->tokens[$i]->text, $texts, true)) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Returns the 1-based column at which a token starts.
     */
    public function column(int $index): int
    {
        $position = $this->tokens[$index]->pos;
        $lineStart = strrpos(substr($this->code, 0, $position), "\n");

        return $position - ($lineStart === false ? -1 : $lineStart);
    }

    /**
     * Returns the source text from one token to another, inclusive.
     */
    public function text(int $from, int $to): string
    {
        $start = $this->tokens[$from]->pos;
        $end = $this->tokens[$to]->pos + strlen($this->tokens[$to]->text);

        return substr($this->code, $start, $end - $start);
    }
}
