<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render;

use function abs;
use function array_slice;
use function count;
use function imagettfbbox;
use function implode;
use function preg_split;

use const PREG_SPLIT_NO_EMPTY;

use function trim;

/**
 * Measures and breaks the lines the social preview card is drawn from.
 *
 * The card is drawn from what a project happens to be called, so nothing
 * about its text is known in advance: the type is measured against the
 * font it will be drawn with, shrunk until the longest line fits the card,
 * and broken into the lines that are left.
 */
final class SocialCardText
{
    /**
     * Returns how wide one line of text is, in pixels, at one size.
     *
     * Text that cannot be measured is reported as empty rather than as an
     * error: a card without a subtitle is still a card.
     */
    public function width(string $font, float $size, string $text): int
    {
        if ($text === '') {
            return 0;
        }

        $box = imagettfbbox($size, 0.0, $font, $text);
        if ($box === false) {
            return 0;
        }

        $left = $box[0];
        $right = $box[2];
        if (!is_int($left) || !is_int($right)) {
            return 0;
        }

        return abs($right - $left);
    }

    /**
     * Returns the largest whole size at which one line fits a width.
     *
     * @param int $largest the size the text is set in when it fits
     * @param int $smallest the size below which it is broken instead of shrunk
     */
    public function fit(string $font, string $text, int $maxWidth, int $largest, int $smallest): int
    {
        for ($size = $largest; $size > $smallest; $size--) {
            if ($this->width($font, (float) $size, $text) <= $maxWidth) {
                return $size;
            }
        }

        return $smallest;
    }

    /**
     * Breaks text into the lines that fit a width, at most a number of them.
     *
     * The last line of a text that does not fit ends in an ellipsis, so a
     * reader is never left believing they read a whole sentence.
     *
     * @return list<string>
     */
    public function wrap(string $font, float $size, string $text, int $maxWidth, int $maxLines): array
    {
        $lines = $this->lines($font, $size, $text, $maxWidth);
        if (count($lines) <= $maxLines) {
            return $lines;
        }

        $kept = array_slice($lines, 0, $maxLines);
        $last = array_pop($kept);
        if ($last === null) {
            return [];
        }

        $kept[] = $this->shortened($font, $size, $last, $maxWidth);

        return $kept;
    }

    /**
     * Breaks text into every line it occupies at one size.
     *
     * @return list<string>
     */
    public function lines(string $font, float $size, string $text, int $maxWidth): array
    {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false || $words === []) {
            return [];
        }

        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;
            if ($line !== '' && $this->width($font, $size, $candidate) > $maxWidth) {
                $lines[] = $line;
                $line = $word;

                continue;
            }

            $line = $candidate;
        }

        $lines[] = $line;

        return $lines;
    }

    /**
     * Returns one line with an ellipsis, shortened until it fits a width.
     */
    public function shortened(string $font, float $size, string $line, int $maxWidth): string
    {
        $characters = preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY);
        if ($characters === false || $characters === []) {
            return $line;
        }

        while ($characters !== [] && $this->width($font, $size, implode('', $characters) . '…') > $maxWidth) {
            array_pop($characters);
        }

        return implode('', $characters) . '…';
    }
}
