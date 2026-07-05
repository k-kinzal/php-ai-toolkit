<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Detects descriptive prose in PHPDoc before the first annotation tag.
 */
final class DescriptivePhpDocTextDetector
{
    /**
     * Reports whether the PHPDoc contains descriptive text before the first @tag.
     */
    public function has(string $docComment): bool
    {
        $lines = explode("\n", $docComment);

        foreach ($lines as $line) {
            $cleaned = $this->cleanLine($line);

            if ($cleaned === '') {
                continue;
            }

            if (str_starts_with($cleaned, '@')) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Strips PHPDoc delimiters and leading asterisks from one line.
     */
    public function cleanLine(string $line): string
    {
        $line = preg_replace('#^\s*/?\*\*/?#', '', $line) ?? $line;
        $line = preg_replace('#^\s*\*/?#', '', $line) ?? $line;
        $line = preg_replace('#\s*\*/$#', '', $line) ?? $line;

        return trim($line);
    }
}
