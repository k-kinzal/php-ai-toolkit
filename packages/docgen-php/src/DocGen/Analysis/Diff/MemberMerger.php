<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Diff;

use function array_merge;
use function array_slice;

use Closure;

/**
 * Merges the members of two revisions into one readable list.
 *
 * Members are matched by name rather than by position: moving a method
 * inside a class does not change what it documents, so a move must not
 * read as one member removed next to one added.
 */
final class MemberMerger
{
    /**
     * Merges two member lists, keeping the order of the head revision.
     *
     * @template T of object
     *
     * @param list<T> $base
     * @param list<T> $head
     * @param Closure(T): string $name
     * @param Closure(T): string $fingerprint
     *
     * @return list<array{item: T, status: string}>
     */
    public function merge(array $base, array $head, Closure $name, Closure $fingerprint): array
    {
        $baseByName = [];
        foreach ($base as $item) {
            $baseByName[$name($item)] = $item;
        }

        $headByName = [];
        foreach ($head as $item) {
            $headByName[$name($item)] = $item;
        }

        $merged = [];
        foreach ($head as $item) {
            $counterpart = $baseByName[$name($item)] ?? null;
            $merged[] = ['item' => $item, 'status' => $this->statusOf($counterpart, $item, $fingerprint)];
        }

        foreach ($base as $index => $item) {
            if (!isset($headByName[$name($item)])) {
                $merged = $this->insert($merged, ['item' => $item, 'status' => DiffStatus::REMOVED], $this->positionFor($base, $index, $merged, $name));
            }
        }

        return $merged;
    }

    /**
     * Compares one member against its counterpart in the base revision.
     *
     * @template T of object
     *
     * @param ?T $counterpart
     * @param T $item
     * @param Closure(T): string $fingerprint
     */
    public function statusOf(?object $counterpart, object $item, Closure $fingerprint): string
    {
        if ($counterpart === null) {
            return DiffStatus::ADDED;
        }

        return $fingerprint($counterpart) === $fingerprint($item) ? DiffStatus::SAME : DiffStatus::MODIFIED;
    }

    /**
     * Returns where a member the head no longer has belongs.
     *
     * The removal is placed behind the nearest member that preceded it in
     * the base revision and is already part of the merged list, so removals
     * keep the order and the neighbourhood they had.
     *
     * @template T of object
     *
     * @param list<T> $base
     * @param list<array{item: T, status: string}> $merged
     * @param Closure(T): string $name
     */
    public function positionFor(array $base, int $index, array $merged, Closure $name): int
    {
        for ($previous = $index - 1; $previous >= 0; $previous--) {
            $position = $this->indexOf($merged, $name($base[$previous]), $name);
            if ($position !== null) {
                return $position + 1;
            }
        }

        return 0;
    }

    /**
     * Returns the position of one named member in a merged list.
     *
     * @template T of object
     *
     * @param list<array{item: T, status: string}> $merged
     * @param Closure(T): string $name
     */
    public function indexOf(array $merged, string $key, Closure $name): ?int
    {
        foreach ($merged as $position => $entry) {
            if ($name($entry['item']) === $key) {
                return $position;
            }
        }

        return null;
    }

    /**
     * Inserts one entry into a merged list at the given position.
     *
     * @template T of object
     *
     * @param list<array{item: T, status: string}> $merged
     * @param array{item: T, status: string} $entry
     *
     * @return list<array{item: T, status: string}>
     */
    public function insert(array $merged, array $entry, int $position): array
    {
        return array_merge(array_slice($merged, 0, $position), [$entry], array_slice($merged, $position));
    }

    /**
     * Looks one member up by name.
     *
     * @template T of object
     *
     * @param list<T> $items
     * @param Closure(T): string $name
     *
     * @return ?T
     */
    public function find(array $items, string $key, Closure $name): ?object
    {
        foreach ($items as $item) {
            if ($name($item) === $key) {
                return $item;
            }
        }

        return null;
    }
}
