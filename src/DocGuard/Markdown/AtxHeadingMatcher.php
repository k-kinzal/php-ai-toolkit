<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Markdown;

use function preg_match;
use function preg_replace;
use function rtrim;
use function strlen;

/**
 * Recognizes ATX headings such as "## Usage" and "## Usage ##".
 */
final class AtxHeadingMatcher
{
    /** @readonly */
    private HeadingTextNormalizer $normalizer;

    /**
     * Creates a matcher with heading text normalization.
     */
    public function __construct(?HeadingTextNormalizer $normalizer = null)
    {
        $this->normalizer = $normalizer ?? new HeadingTextNormalizer();
    }

    /**
     * Returns the heading on a line whose indentation is already removed, or null.
     *
     * The optional closing sequence of "#" characters is not part of the text.
     */
    public function match(string $content, int $lineNumber): ?Heading
    {
        if (preg_match('/^(#{1,6})(?:[ \t]+(.*))?$/', $content, $matches) !== 1) {
            return null;
        }

        $text = rtrim($matches[2] ?? '', " \t");
        if (preg_match('/^#+$/', $text) === 1) {
            $text = '';
        }

        $text = preg_replace('/[ \t]+#+$/', '', $text) ?? $text;

        return new Heading(strlen($matches[1]), $this->normalizer->normalize($text), $lineNumber);
    }
}
