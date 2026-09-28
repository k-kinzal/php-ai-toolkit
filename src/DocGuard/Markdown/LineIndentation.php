<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Markdown;

use function strlen;

/**
 * Measures the leading indentation of a Markdown line.
 */
final class LineIndentation
{
    /**
     * Returns the indentation width, expanding each tab to the next multiple of four columns.
     */
    public function width(string $line): int
    {
        $width = 0;
        $length = strlen($line);
        for ($index = 0; $index < $length; $index++) {
            if ($line[$index] === ' ') {
                $width++;
            } elseif ($line[$index] === "\t") {
                $width += 4 - ($width % 4);
            } else {
                break;
            }
        }

        return $width;
    }
}
