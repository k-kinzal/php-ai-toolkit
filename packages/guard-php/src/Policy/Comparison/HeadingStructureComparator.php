<?php

declare(strict_types=1);

namespace Guard\Policy\Comparison;

use function array_map;
use function array_merge;
use function array_values;

use Guard\Config\Value\DeclaredHeading;
use Guard\Config\Value\DocumentConfig;
use Guard\Reporting\HeadingViolation;
use Guard\Reporting\HeadingViolationFactory;
use Guard\Structure\Markdown\Heading;

/**
 * Compares the headings of a document with its declared structure.
 *
 * Headings are equal when their level and normalized text are equal. After
 * the longest common subsequence is matched, a declared heading that is
 * dropped in one place and added in another is reported as moved; the other
 * unmatched headings are classified gap by gap.
 */
final class HeadingStructureComparator
{
    /** @readonly */
    private HeadingSequenceAligner $aligner;

    /** @readonly */
    private HeadingHunkClassifier $hunkClassifier;

    /** @readonly */
    private HeadingViolationFactory $violationFactory;

    /**
     * Creates a comparator from sequence alignment and violation classification.
     */
    public function __construct(
        ?HeadingSequenceAligner $aligner = null,
        ?HeadingHunkClassifier $hunkClassifier = null,
        ?HeadingViolationFactory $violationFactory = null,
    ) {
        $this->aligner = $aligner ?? new HeadingSequenceAligner();
        $this->hunkClassifier = $hunkClassifier ?? new HeadingHunkClassifier();
        $this->violationFactory = $violationFactory ?? new HeadingViolationFactory();
    }

    /**
     * Returns the structure violations of a document's actual headings.
     *
     * @param list<Heading> $actual
     * @return list<HeadingViolation>
     */
    public function compare(DocumentConfig $document, array $actual, string $configName): array
    {
        $declared = $document->headings ?? [];
        $hunks = $this->hunks($this->aligner->align(
            array_map(static fn (DeclaredHeading $heading): string => $heading->notation(), $declared),
            array_map(static fn (Heading $heading): string => $heading->notation(), $actual),
        ));

        $violations = [];
        foreach ($hunks as $removedHunk => $hunk) {
            foreach ($hunk['removed'] as $removedKey => $expectedIndex) {
                foreach ($hunks as $addedHunk => $candidate) {
                    foreach ($candidate['added'] as $addedKey => $actualIndex) {
                        if (isset($hunks[$addedHunk]['added'][$addedKey]) && $declared[$expectedIndex]->notation() === $actual[$actualIndex]->notation()) {
                            $violations[] = $this->violationFactory->movedHeading($document->path, $declared, $expectedIndex, $actual[$actualIndex], $configName);
                            unset($hunks[$removedHunk]['removed'][$removedKey], $hunks[$addedHunk]['added'][$addedKey]);
                            continue 3;
                        }
                    }
                }
            }
        }

        foreach ($hunks as $hunk) {
            $violations = array_merge($violations, $this->hunkClassifier->classify(
                $document->path,
                $declared,
                $actual,
                array_values($hunk['removed']),
                array_values($hunk['added']),
                $configName,
            ));
        }

        return $violations;
    }

    /**
     * Groups the unmatched alignment steps into the gaps between matched headings.
     *
     * @param list<array{0: ?int, 1: ?int}> $steps
     * @return list<array{removed: array<int, int>, added: array<int, int>}>
     */
    public function hunks(array $steps): array
    {
        $hunks = [];
        $current = ['removed' => [], 'added' => []];
        foreach ($steps as [$expectedIndex, $actualIndex]) {
            if ($expectedIndex !== null && $actualIndex !== null) {
                if ($current['removed'] !== [] || $current['added'] !== []) {
                    $hunks[] = $current;
                    $current = ['removed' => [], 'added' => []];
                }
            } elseif ($expectedIndex !== null) {
                $current['removed'][] = $expectedIndex;
            } elseif ($actualIndex !== null) {
                $current['added'][] = $actualIndex;
            }
        }

        if ($current['removed'] !== [] || $current['added'] !== []) {
            $hunks[] = $current;
        }

        return $hunks;
    }
}
