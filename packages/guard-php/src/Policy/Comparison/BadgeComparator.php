<?php

declare(strict_types=1);

namespace Guard\Policy\Comparison;

use function array_map;
use function array_unshift;

use Guard\Policy\Definition\BadgeEntry;
use Guard\Policy\Diagnostic\DocumentViolationFactory;
use Guard\Policy\Diagnostic\HeadingViolation;
use Guard\Structure\Markdown\Badge\Badge;
use Guard\Structure\Markdown\Badge\BadgeBlock;

use function implode;
use function sprintf;

/**
 * Checks the badge block below a document's title against the declared badge order.
 */
final class BadgeComparator
{
    /**
     * Returns the violations of one document's badge block.
     *
     * @param list<BadgeEntry> $declared
     * @return list<HeadingViolation>
     */
    public function compare(string $path, array $declared, BadgeBlock $block, string $configName): array
    {
        $factory = new DocumentViolationFactory();
        $badges = $block->badges;
        if ($badges === []) {
            return [$factory->missingBadges($path, $block->title, $block->earlyLine, $declared, $configName)];
        }
        $matrix = [];
        foreach ($badges as $badge) {
            $matrix[] = array_map(static fn (BadgeEntry $entry): bool => $entry->accepts($badge->label, $badge->image), $declared);
        }
        $result = (new SequenceMatcher())->match(
            array_map(static fn (BadgeEntry $entry): array => [$entry->optional, $entry->repeat], $declared),
            $matrix,
        );
        $violations = [];
        foreach ($result->unexpected as [$item]) {
            $violations[] = $factory->unexpectedBadge($path, $badges[$item], $this->declaration($declared, $badges[$item]), $declared, $configName);
        }
        foreach ($result->missing as $slot) {
            $violations[] = $factory->missingBadge($path, $declared[$slot], $this->position($declared, $slot), $configName);
        }

        return $violations;
    }

    /**
     * Describes where a badge belongs by the nearest required badge before it, or the optional badges it follows.
     *
     * @param list<BadgeEntry> $declared
     */
    public function position(array $declared, int $slot): string
    {
        $optional = [];
        for ($index = $slot - 1; $index >= 0; $index--) {
            if (!$declared[$index]->optional) {
                return sprintf('after the "%s" badge', $declared[$index]->name);
            }
            array_unshift($optional, $declared[$index]->name);
        }

        return $optional === [] ? 'as the first badge' : sprintf('after any %s badges', implode(', ', $optional));
    }

    /**
     * Returns the first declaration the badge satisfies, or null when it matches none.
     *
     * @param list<BadgeEntry> $declared
     */
    public function declaration(array $declared, Badge $badge): ?BadgeEntry
    {
        foreach ($declared as $entry) {
            if ($entry->accepts($badge->label, $badge->image)) {
                return $entry;
            }
        }

        return null;
    }
}
