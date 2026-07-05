<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

use ParseError;

/**
 * Parses PHP files into native tokenizer tokens for file-level rules.
 */
final class FileTokenParser
{
    /**
     * Returns tokenizer tokens for a readable PHP file, or null when unavailable.
     *
     * @return array<int, array{int, string, int}|string>|null
     */
    public function parse(string $path): ?array
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $source = file_get_contents($path);
        if ($source === false) {
            return null;
        }

        try {
            return token_get_all($source, TOKEN_PARSE);
        } catch (ParseError) {
            return null;
        }
    }
}
