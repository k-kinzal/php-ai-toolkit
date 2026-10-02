<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Config;

use function is_string;
use function sprintf;

use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Markdown\AtxHeadingMatcher;

/**
 * Reads one declared heading written in ATX notation, such as '## Usage'.
 *
 * The notation is parsed like an ATX heading in a document, so a closing
 * "#" sequence and extra whitespace are ignored in the same way.
 */
final class DeclaredHeadingReader
{
    /** @readonly */
    private AtxHeadingMatcher $atxMatcher;

    /**
     * Creates a reader from ATX heading recognition.
     */
    public function __construct(?AtxHeadingMatcher $atxMatcher = null)
    {
        $this->atxMatcher = $atxMatcher ?? new AtxHeadingMatcher();
    }

    /**
     * Reads a declared heading from its notation.
     *
     * @param mixed $value
     *
     * @throws DocGuardException when the value is not a heading in ATX notation
     */
    public function read($value, string $context): DeclaredHeading
    {
        $heading = is_string($value) ? $this->atxMatcher->match($value, 0) : null;
        if ($heading === null) {
            throw new DocGuardException(sprintf(
                'Invalid doc-guard.yaml: "%s" must be a heading in ATX notation such as \'## Usage\': one to six "#" characters, a space, and the heading text. Quote the value, because YAML reads an unquoted "#" as the start of a comment.',
                $context,
            ));
        }

        return new DeclaredHeading($heading->level, $heading->text);
    }
}
