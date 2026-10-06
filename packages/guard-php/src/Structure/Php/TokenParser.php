<?php

declare(strict_types=1);

namespace Guard\Structure\Php;

use Guard\Structure\Source;
use Guard\Structure\Structurer;
use PhpToken;

/**
 * Tokenizes PHP without executing it or reading files.
 */
final class TokenParser implements Structurer
{
    /**
     * Builds the token structure shared by metrics and literal configuration values.
     */
    public function structure(Source $source): Tokens
    {
        return new Tokens(array_values(PhpToken::tokenize($source->text())));
    }
}
