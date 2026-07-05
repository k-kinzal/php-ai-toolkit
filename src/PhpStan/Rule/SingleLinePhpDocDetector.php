<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule;

/**
 * Detects PHPDoc comments written on one line.
 */
final class SingleLinePhpDocDetector
{
    /**
     * Reports whether PHPDoc text contains no newline.
     */
    public function isSingleLine(string $text): bool
    {
        return strpos($text, "\n") === false;
    }
}
