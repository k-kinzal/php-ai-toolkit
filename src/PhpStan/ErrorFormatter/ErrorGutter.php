<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\ErrorFormatter;

use function max;
use function str_pad;

use const STR_PAD_LEFT;

use function strlen;

/**
 * Formats line gutters for PHPStan human output.
 */
final class ErrorGutter
{
    /**
     * Calculates a line-number gutter width for errors in one file.
     *
     * @param list<\PHPStan\Analyser\Error> $errors file-specific errors
     */
    public function width(array $errors): int
    {
        $maxLine = 1;
        foreach ($errors as $error) {
            $line = $error->getLine();
            if ($line !== null && $line > $maxLine) {
                $maxLine = $line;
            }
        }

        return max(3, strlen((string) $maxLine));
    }

    /**
     * Pads one gutter line to the target width.
     */
    public function line(string $line, int $width): string
    {
        return str_pad($line, $width, ' ', STR_PAD_LEFT);
    }
}
