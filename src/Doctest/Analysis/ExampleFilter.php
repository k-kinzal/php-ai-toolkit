<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Analysis;

use function str_contains;
use function strtolower;

/**
 * Selects the examples a run is limited to.
 *
 * The filter matches the example identifier, so the string printed next to a
 * failing example is exactly the string that runs that example on its own.
 */
final class ExampleFilter
{
    /**
     * Returns the examples that match the filter, or all of them when there is none.
     *
     * @param list<Example> $examples
     * @return list<Example>
     */
    public function apply(array $examples, ?string $filter): array
    {
        if ($filter === null || $filter === '') {
            return $examples;
        }

        $matched = [];
        foreach ($examples as $example) {
            if ($this->matches($example, $filter)) {
                $matched[] = $example;
            }
        }

        return $matched;
    }

    /**
     * Reports whether one example matches the filter.
     *
     * Matching is case-insensitive on a substring of the identifier, so a
     * method name alone selects every example of that method.
     */
    public function matches(Example $example, string $filter): bool
    {
        $needle = strtolower($filter);

        return str_contains(strtolower($example->id()), $needle)
            || str_contains(strtolower($example->target->reportPath()), $needle);
    }
}
