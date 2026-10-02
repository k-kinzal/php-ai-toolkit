<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Analysis;

use function array_values;
use function count;

use Toolkit\DocGuard\Config\DeclaredHeading;
use Toolkit\DocGuard\Markdown\Heading;

/**
 * Classifies the unmatched headings between two matched headings.
 *
 * Within one gap, a declared and an actual heading with the same text are a
 * level change. When the remaining declared and actual headings pair up one
 * to one at the same levels, each pair is a rename. Otherwise every remaining
 * actual heading is an added heading and every remaining declared heading is
 * a missing one, because guessing which heading replaced which would point
 * the fix at the wrong section.
 */
final class HeadingHunkClassifier
{
    /** @readonly */
    private ViolationFactory $violationFactory;

    /**
     * Creates a classifier from violation construction.
     */
    public function __construct(?ViolationFactory $violationFactory = null)
    {
        $this->violationFactory = $violationFactory ?? new ViolationFactory();
    }

    /**
     * Returns the violations for the removed declared indexes and added actual indexes of one gap.
     *
     * @param list<DeclaredHeading> $declared
     * @param list<Heading> $actual
     * @param list<int> $removed
     * @param list<int> $added
     * @return list<Violation>
     */
    public function classify(string $path, array $declared, array $actual, array $removed, array $added, string $configName): array
    {
        $violations = [];
        foreach ($removed as $removedKey => $expectedIndex) {
            foreach ($added as $addedKey => $actualIndex) {
                if ($declared[$expectedIndex]->text === $actual[$actualIndex]->text) {
                    $violations[] = $this->violationFactory->changedHeadingLevel($path, $declared[$expectedIndex], $actual[$actualIndex], $configName);
                    unset($removed[$removedKey], $added[$addedKey]);
                    break;
                }
            }
        }

        $removed = array_values($removed);
        $added = array_values($added);
        if ($this->pairsAsRenames($declared, $actual, $removed, $added)) {
            foreach ($removed as $position => $expectedIndex) {
                $violations[] = $this->violationFactory->renamedHeading($path, $declared[$expectedIndex], $actual[$added[$position]], $configName);
            }

            return $violations;
        }

        foreach ($added as $actualIndex) {
            $violations[] = $this->violationFactory->unexpectedHeading($path, $actual[$actualIndex], $configName);
        }

        foreach ($removed as $expectedIndex) {
            $violations[] = $this->violationFactory->missingHeading($path, $declared, $expectedIndex, $configName);
        }

        return $violations;
    }

    /**
     * Returns whether the declared and actual headings of a gap pair up one to one at the same levels.
     *
     * @param list<DeclaredHeading> $declared
     * @param list<Heading> $actual
     * @param list<int> $removed
     * @param list<int> $added
     */
    public function pairsAsRenames(array $declared, array $actual, array $removed, array $added): bool
    {
        if ($removed === [] || count($removed) !== count($added)) {
            return false;
        }

        foreach ($removed as $position => $expectedIndex) {
            if ($declared[$expectedIndex]->level !== $actual[$added[$position]]->level) {
                return false;
            }
        }

        return true;
    }
}
