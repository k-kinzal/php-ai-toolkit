<?php

declare(strict_types=1);

namespace Guard\Policy\Comparison;

use function array_filter;
use function array_keys;
use function array_map;
use function array_values;

use Guard\Config\Value\DocumentConfig;
use Guard\Config\Value\OutlineEntry;
use Guard\Reporting\DocumentViolationFactory;
use Guard\Reporting\HeadingViolation;
use Guard\Structure\Markdown\Heading;

use function implode;
use function max;
use function sprintf;
use function str_repeat;

/**
 * Checks a document's headings against its declared alternative outlines.
 *
 * An outline only sees headings down to its deepest declared level, so deeper headings stay free content.
 * The document passes when it follows any outline; otherwise the outline with the fewest findings is reported,
 * and on a tie the one whose named headings the document uses most.
 */
final class OutlineComparator
{
    /**
     * Returns no violations when an outline matches, or the violations of the closest outline.
     *
     * @param list<Heading> $actual every heading of the document
     * @return list<HeadingViolation>
     */
    public function compare(DocumentConfig $document, array $actual, string $configName): array
    {
        $closest = null;
        foreach ($document->outlines as $name => $entries) {
            $headings = $this->visible($entries, $actual);
            $result = (new SequenceMatcher())->match(
                array_map(static fn (OutlineEntry $entry): array => [$entry->optional, $entry->repeat], $entries),
                $this->accepts($entries, $headings),
            );
            if ($result->count() === 0) {
                return [];
            }
            $named = $this->named($entries, $headings);
            if ($closest === null || $result->count() < $closest[3]->count() || ($result->count() === $closest[3]->count() && $named > $closest[4])) {
                $closest = [$name, $entries, $headings, $result, $named];
            }
        }
        if ($closest === null) {
            return [];
        }
        [$name, $entries, $headings, $result] = $closest;

        return $this->violations($document->path, $name, array_keys($document->outlines), $entries, $headings, $result, $configName);
    }

    /**
     * Counts the headings that a named entry of the outline accepts.
     *
     * Among outlines with equally many findings, the one whose named sections the document already uses is
     * the one its author followed, so its findings describe the smallest edit.
     *
     * @param list<OutlineEntry> $entries
     * @param list<Heading> $headings
     */
    public function named(array $entries, array $headings): int
    {
        $count = 0;
        foreach ($headings as $heading) {
            foreach ($entries as $entry) {
                if (!$entry->wildcard() && $entry->names($heading->level, $heading->text)) {
                    $count++;
                    break;
                }
            }
        }

        return $count;
    }

    /**
     * Returns the headings no deeper than the outline's deepest entry.
     *
     * @param list<OutlineEntry> $entries
     * @param list<Heading> $actual
     * @return list<Heading>
     */
    public function visible(array $entries, array $actual): array
    {
        $depth = 1;
        foreach ($entries as $entry) {
            foreach ($entry->headings() as $heading) {
                $depth = max($depth, $heading->level);
            }
        }

        return array_values(array_filter($actual, static fn (Heading $heading): bool => $heading->level <= $depth));
    }

    /**
     * Builds the item-by-slot acceptance matrix. A wildcard skips texts that another entry of its level names.
     *
     * @param list<OutlineEntry> $entries
     * @param list<Heading> $headings
     * @return list<list<bool>>
     */
    public function accepts(array $entries, array $headings): array
    {
        $named = [];
        foreach ($entries as $entry) {
            foreach ($entry->wildcard() ? [] : $entry->headings() as $name) {
                $named[$name->level][$name->text] = true;
            }
        }
        $matrix = [];
        foreach ($headings as $heading) {
            $row = [];
            foreach ($entries as $entry) {
                $row[] = $entry->wildcard()
                    ? $entry->level === $heading->level && !isset($named[$heading->level][$heading->text])
                    : $entry->names($heading->level, $heading->text);
            }
            $matrix[] = $row;
        }

        return $matrix;
    }

    /**
     * Converts the closest outline's mismatches into violations.
     *
     * @param list<string> $outlines
     * @param list<OutlineEntry> $entries
     * @param list<Heading> $headings
     * @return list<HeadingViolation>
     */
    public function violations(string $path, string $name, array $outlines, array $entries, array $headings, SequenceResult $result, string $configName): array
    {
        $factory = new DocumentViolationFactory();
        $violations = [];
        foreach ($result->unexpected as [$item, $slots]) {
            $expected = array_map(fn (int $slot): string => $this->describe($entries[$slot]), $slots);
            $violations[] = $factory->unexpectedOutlineHeading($path, $headings[$item], $expected, $name, $outlines, $configName);
        }
        foreach ($result->missing as $slot) {
            $violations[] = $factory->missingOutlineHeading($path, $entries[$slot]->notations(), $this->position($entries, $slot), $name, $outlines, $configName);
        }

        return $violations;
    }

    /**
     * Describes where an entry belongs by the nearest named entry before it.
     *
     * @param list<OutlineEntry> $entries
     */
    public function position(array $entries, int $slot): string
    {
        for ($index = $slot - 1; $index >= 0; $index--) {
            if (!$entries[$index]->wildcard()) {
                return 'after ' . $this->describe($entries[$index]);
            }
        }

        return $slot === 0 ? 'as the first heading' : sprintf('after the first level-%d heading', $entries[0]->level);
    }

    /**
     * Describes the heading one outline entry accepts.
     */
    public function describe(OutlineEntry $entry): string
    {
        return $entry->wildcard()
            ? sprintf('any other "%s" heading', str_repeat('#', $entry->level))
            : implode(' or ', array_map(static fn (string $notation): string => '"' . $notation . '"', $entry->notations()));
    }
}
