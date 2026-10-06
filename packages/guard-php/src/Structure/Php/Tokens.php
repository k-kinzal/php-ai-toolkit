<?php

declare(strict_types=1);

namespace Guard\Structure\Php;

use Guard\Structure\Subject;
use PhpToken;

/**
 * PHP tokens shared by every structure derived from the same source.
 */
final class Tokens implements Subject
{
    /**
     * @param list<PhpToken> $tokens
     */
    public function __construct(private array $tokens)
    {
    }
    /**
     * @return list<PhpToken>
     */
    public function all(): array
    {
        return $this->tokens;
    }
}
