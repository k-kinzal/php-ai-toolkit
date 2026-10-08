<?php

declare(strict_types=1);

namespace Guard\Config\Reader;

use function array_key_exists;
use function array_slice;
use function array_values;

use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Definition\DeclaredHeading;
use Guard\Policy\Definition\OutlineEntry;

use function is_array;
use function is_bool;
use function is_string;
use function sprintf;

/**
 * Reads the alternative outlines a document may follow.
 *
 * An outline is an ordered list of headings. A plain string such as '## License' is required exactly once.
 * A mapping names its "heading", or several accepted headings in "one_of", and may add "optional" (may be
 * absent) and "repeat" (may occur more than once).
 */
final class OutlineConfigReader
{
    /**
     * Reads a mapping from outline names to their entry lists.
     *
     * @param mixed $value
     * @return array<string, list<OutlineEntry>>
     *
     * @throws PolicyException when the outlines are not a non-empty mapping of non-empty heading lists
     */
    public function read($value, string $context): array
    {
        $label = $context . '.outlines';
        if (!is_array($value) || $value === [] || array_values($value) === $value) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s" must be a non-empty mapping from outline names to heading lists, such as {package: [\'# *\', \'## License\']}.', $label));
        }
        $outlines = [];
        foreach ($value as $name => $entries) {
            if (!is_string($name) || $name === '' || !is_array($entries) || $entries === [] || array_values($entries) !== $entries) {
                throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s.%s" must be a non-empty list of headings such as \'## Usage\'.', $label, (string) $name));
            }
            $list = [];
            foreach ($entries as $index => $entry) {
                $list[] = $this->entry($entry, sprintf('%s.%s[%d]', $label, $name, $index));
            }
            $outlines[$name] = $list;
        }

        return $outlines;
    }

    /**
     * Reads one outline entry from a heading string or a mapping with occurrence flags.
     *
     * @param mixed $entry
     *
     * @throws PolicyException when the entry is not a heading or has invalid flags
     */
    public function entry($entry, string $context): OutlineEntry
    {
        if (!is_array($entry)) {
            $heading = (new DeclaredHeadingReader())->read($entry, $context);

            return new OutlineEntry($heading->level, $heading->text);
        }
        (new HeadingConfigKeyValidator())->rejectUnknown($entry, ['heading', 'one_of', 'optional', 'repeat'], $context);
        $optional = $entry['optional'] ?? false;
        $repeat = $entry['repeat'] ?? false;
        if (!is_bool($optional) || !is_bool($repeat)) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s.optional" and "%s.repeat" must be true or false.', $context, $context));
        }
        if (array_key_exists('one_of', $entry) === array_key_exists('heading', $entry)) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s" must set exactly one of "heading" and "one_of".', $context));
        }
        if (!array_key_exists('one_of', $entry)) {
            $heading = (new DeclaredHeadingReader())->read($entry['heading'], $context . '.heading');

            return new OutlineEntry($heading->level, $heading->text, $optional, $repeat);
        }
        $headings = $this->alternatives($entry['one_of'], $context . '.one_of');

        return new OutlineEntry($headings[0]->level, $headings[0]->text, $optional, $repeat, array_slice($headings, 1));
    }

    /**
     * Reads the named headings one outline position accepts.
     *
     * @param mixed $value
     * @return non-empty-list<DeclaredHeading>
     *
     * @throws PolicyException when the value is not a non-empty list of named headings
     */
    public function alternatives($value, string $context): array
    {
        if (!is_array($value) || $value === [] || array_values($value) !== $value) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s" must be a non-empty list of headings such as [\'## Vision\', \'## Product Vision\'].', $context));
        }
        $headings = [];
        foreach ($value as $index => $entry) {
            $heading = (new DeclaredHeadingReader())->read($entry, sprintf('%s[%d]', $context, $index));
            if ($heading->text === '*') {
                throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s[%d]" cannot be a wildcard. Use "heading: \'%s\'" for a position that accepts any heading.', $context, $index, $heading->notation()));
            }
            $headings[] = $heading;
        }

        return $headings;
    }
}
