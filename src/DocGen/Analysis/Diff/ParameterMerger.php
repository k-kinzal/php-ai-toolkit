<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Diff;

use Toolkit\DocGen\Analysis\Model\ParameterDoc;

/**
 * Merges the parameter lists of two revisions of one declaration.
 *
 * Parameters are matched by position through their names, because the
 * order of a parameter list is part of the contract: a reordered list is a
 * changed list, unlike a reordered method inside a class.
 */
final class ParameterMerger
{
    /** @readonly */
    private LcsMatcher $matcher;

    /** @readonly */
    private SymbolFingerprint $fingerprint;

    /**
     * Creates a parameter merger from its matching collaborators.
     */
    public function __construct(?LcsMatcher $matcher = null, ?SymbolFingerprint $fingerprint = null)
    {
        $this->matcher = $matcher ?? new LcsMatcher();
        $this->fingerprint = $fingerprint ?? new SymbolFingerprint();
    }

    /**
     * Merges two parameter lists and records the state of each parameter.
     *
     * @param list<ParameterDoc> $base
     * @param list<ParameterDoc> $head
     * @param string $ownerKey the diff key of the owning declaration
     *
     * @return list<ParameterDoc>
     */
    public function merge(array $base, array $head, string $ownerKey, DiffIndex $index): array
    {
        $parameters = [];
        foreach ($this->matcher->match($this->names($base), $this->names($head)) as $operation) {
            $parameter = $operation['head'] !== null ? $head[$operation['head']] : null;
            $parameter ??= $operation['base'] !== null ? $base[$operation['base']] : null;
            if ($parameter === null) {
                continue;
            }

            $index->mark($index->keys()->parameter($ownerKey, $parameter->name), $this->statusOf($base, $head, $operation));
            $parameters[] = $parameter;
        }

        return $parameters;
    }

    /**
     * Determines the state of one matched parameter position.
     *
     * @param list<ParameterDoc> $base
     * @param list<ParameterDoc> $head
     * @param array{base: ?int, head: ?int} $operation
     */
    public function statusOf(array $base, array $head, array $operation): string
    {
        if ($operation['base'] === null) {
            return DiffStatus::ADDED;
        }

        if ($operation['head'] === null) {
            return DiffStatus::REMOVED;
        }

        return $this->fingerprint->parameter($base[$operation['base']]) === $this->fingerprint->parameter($head[$operation['head']])
            ? DiffStatus::SAME
            : DiffStatus::MODIFIED;
    }

    /**
     * Lists the names of a parameter list in declaration order.
     *
     * @param list<ParameterDoc> $parameters
     *
     * @return list<string>
     */
    public function names(array $parameters): array
    {
        $names = [];
        foreach ($parameters as $parameter) {
            $names[] = $parameter->name;
        }

        return $names;
    }
}
