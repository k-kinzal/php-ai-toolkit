<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Reference;

use function array_values;

use const PHP_INT_MAX;

use function sprintf;
use function strtolower;
use function uksort;
use function usort;

/**
 * Query index over all collected symbol references.
 *
 * Beside the flat lookups the index answers the two derived questions the
 * documentation pages ask: which production references a symbol has, grouped
 * by kind and kept apart from test code, and the reverse direction, namely
 * which documented symbols the body of one method itself references.
 */
final class UsageIndex
{
    /**
     * Rank of every known usage kind within the grouped output.
     *
     * The order runs from the strongest structural relation, inheritance,
     * down to incidental references such as plain type mentions, so a
     * grouped listing reads top down. Kinds missing from this map sort
     * after all known kinds, alphabetically by kind name.
     */
    private const KIND_ORDER = [
        'extends' => 0,
        'implements' => 1,
        'use-trait' => 2,
        'new' => 3,
        'static-call' => 4,
        'method-call' => 5,
        'class-const' => 6,
        'instanceof' => 7,
        'attribute' => 8,
        'type' => 9,
        'function-call' => 10,
    ];

    /**
     * Kinds that represent an outgoing call, construction or constant read.
     *
     * Only these kinds describe what a method body actually does at runtime.
     * Inheritance, trait use, type mentions, instanceof checks and attributes
     * are declarations about a symbol rather than calls made by it, so they
     * are excluded from the outgoing direction.
     */
    private const CALL_KINDS = [
        'method-call' => true,
        'static-call' => true,
        'new' => true,
        'function-call' => true,
        'class-const' => true,
    ];

    /** @var array<string, list<Usage>> */
    private array $byType = [];

    /** @var array<string, list<Usage>> */
    private array $byMember = [];

    /** @var array<string, list<Usage>> */
    private array $byOriginType = [];

    /** @var array<string, list<Usage>> */
    private array $byOriginMember = [];

    /** @var array<string, bool> */
    private array $seen = [];

    /**
     * Indexes a list of usages, dropping exact duplicates.
     *
     * @param list<Usage> $usages
     */
    public function build(array $usages): void
    {
        foreach ($usages as $usage) {
            $identity = sprintf(
                '%s|%s|%s|%s|%d',
                strtolower($usage->targetFqcn),
                strtolower($usage->member ?? ''),
                $usage->kind,
                $usage->file,
                $usage->line,
            );
            if (isset($this->seen[$identity])) {
                continue;
            }

            $this->seen[$identity] = true;
            $this->byType[strtolower($usage->targetFqcn)][] = $usage;
            if ($usage->member !== null) {
                $this->byMember[strtolower($usage->targetFqcn . '::' . $usage->member)][] = $usage;
            }

            $this->indexOrigin($usage);
        }
    }

    /**
     * Indexes one usage under the class and member its body appears in.
     *
     * Usages of a kind that is not a call and usages without a known origin
     * class carry no outgoing information and are skipped. This is called by
     * build() after duplicate rejection and needs no separate invocation.
     */
    public function indexOrigin(Usage $usage): void
    {
        if ($usage->fromFqcn === null || !isset(self::CALL_KINDS[$usage->kind])) {
            return;
        }

        $this->byOriginType[strtolower($usage->fromFqcn)][] = $usage;
        if ($usage->fromMember !== null) {
            $this->byOriginMember[strtolower($usage->fromFqcn . '::' . $usage->fromMember)][] = $usage;
        }
    }

    /**
     * Returns all references to a class-like symbol ordered by location.
     *
     * Dev references, that is references made from test or other dev-only
     * sources, are included by default so the behaviour of existing callers
     * is unchanged. Pass false to keep production references only.
     *
     * @return list<Usage>
     */
    public function forType(string $fqcn, bool $includeDev = true): array
    {
        return $this->sorted($this->filtered($this->byType[strtolower($fqcn)] ?? [], $includeDev));
    }

    /**
     * Returns all references to one member of a symbol ordered by location.
     *
     * Dev references are included by default, matching forType().
     *
     * @return list<Usage>
     */
    public function forMember(string $fqcn, string $member, bool $includeDev = true): array
    {
        return $this->sorted($this->filtered($this->byMember[strtolower($fqcn . '::' . $member)] ?? [], $includeDev));
    }

    /**
     * Returns the references to a class-like symbol grouped by kind.
     *
     * Groups follow KIND_ORDER and each group is ordered by file and line.
     * Empty groups are omitted, so the caller can render every returned
     * group unconditionally.
     *
     * @return array<string, list<Usage>>
     */
    public function forTypeGrouped(string $fqcn, bool $includeDev): array
    {
        return $this->groupByKind($this->forType($fqcn, $includeDev));
    }

    /**
     * Returns the references to one member grouped by kind.
     *
     * @return array<string, list<Usage>>
     */
    public function forMemberGrouped(string $fqcn, string $member, bool $includeDev): array
    {
        return $this->groupByKind($this->forMember($fqcn, $member, $includeDev));
    }

    /**
     * Returns the call-like references made by the body of one member.
     *
     * Only the kinds listed in CALL_KINDS are reported. References are
     * deduplicated by target, member and kind so a symbol called in a loop
     * or on several lines appears once, at its first location. References to
     * the owning class itself are kept, as a method calling a sibling method
     * is a genuine outgoing call.
     *
     * @return list<Usage>
     */
    public function callsFrom(string $fqcn, string $member): array
    {
        return $this->deduplicateTargets($this->byOriginMember[strtolower($fqcn . '::' . $member)] ?? []);
    }

    /**
     * Returns the call-like references made by any member of one class.
     *
     * @return list<Usage>
     */
    public function callsFromType(string $fqcn): array
    {
        return $this->deduplicateTargets($this->byOriginType[strtolower($fqcn)] ?? []);
    }

    /**
     * Groups usages by kind, ordering the groups and their contents.
     *
     * @param list<Usage> $usages
     *
     * @return array<string, list<Usage>>
     */
    public function groupByKind(array $usages): array
    {
        $groups = [];
        foreach ($this->sorted($usages) as $usage) {
            $groups[$usage->kind][] = $usage;
        }

        uksort($groups, static function (string $first, string $second): int {
            $firstRank = self::KIND_ORDER[$first] ?? PHP_INT_MAX;
            $secondRank = self::KIND_ORDER[$second] ?? PHP_INT_MAX;

            return $firstRank === $secondRank ? $first <=> $second : $firstRank <=> $secondRank;
        });

        return $groups;
    }

    /**
     * Drops the dev usages from a list unless they are wanted.
     *
     * @param list<Usage> $usages
     *
     * @return list<Usage>
     */
    public function filtered(array $usages, bool $includeDev): array
    {
        if ($includeDev) {
            return $usages;
        }

        $production = [];
        foreach ($usages as $usage) {
            if (!$usage->fromDev) {
                $production[] = $usage;
            }
        }

        return $production;
    }

    /**
     * Keeps the first usage per target, member and kind, ordered by location.
     *
     * @param list<Usage> $usages
     *
     * @return list<Usage>
     */
    public function deduplicateTargets(array $usages): array
    {
        $unique = [];
        foreach ($this->sorted($usages) as $usage) {
            $key = strtolower($usage->targetFqcn . '::' . ($usage->member ?? '')) . '|' . $usage->kind;
            if (!isset($unique[$key])) {
                $unique[$key] = $usage;
            }
        }

        return array_values($unique);
    }

    /**
     * Sorts usages by file path and line.
     *
     * @param list<Usage> $usages
     *
     * @return list<Usage>
     */
    public function sorted(array $usages): array
    {
        usort($usages, static function (Usage $a, Usage $b): int {
            if ($a->file !== $b->file) {
                return $a->file <=> $b->file;
            }

            return $a->line <=> $b->line;
        });

        return $usages;
    }
}
