<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpStan\Rule\ControlFlow;

use function array_key_exists;
use function in_array;
use function sort;

/**
 * Answers which classes of the analysed code a value of a given type can actually be.
 *
 * Only instantiable classes count: an interface and an abstract class name a set of
 * classes rather than being one, so `$shape::class` never reads back as either of them.
 */
final class SubtypeIndex
{
    /**
     * @var array<string, list<string>>
     * @readonly
     */
    private array $subtypes;

    /**
     * Creates the index from what the class collector gathered.
     *
     * @param list<array{name: string, instantiable: bool, ancestors: list<string>}> $classes
     */
    public function __construct(array $classes)
    {
        $subtypes = [];
        foreach ($classes as $class) {
            if (!$class['instantiable']) {
                continue;
            }

            foreach ($class['ancestors'] as $ancestor) {
                if (!array_key_exists($ancestor, $subtypes)) {
                    $subtypes[$ancestor] = [];
                }

                if (in_array($class['name'], $subtypes[$ancestor], true)) {
                    continue;
                }

                $subtypes[$ancestor][] = $class['name'];
            }
        }

        foreach ($subtypes as $ancestor => $names) {
            sort($names);
            $subtypes[$ancestor] = $names;
        }

        $this->subtypes = $subtypes;
    }

    /**
     * Returns every instantiable class below the named types, in a fixed order.
     *
     * The order is alphabetical rather than the order the files happened to be analysed
     * in, so the same code reports the same message on every run.
     *
     * @param list<string> $rootNames
     * @return list<string>
     */
    public function instantiableUnder(array $rootNames): array
    {
        $names = [];
        foreach ($rootNames as $rootName) {
            foreach ($this->subtypes[$rootName] ?? [] as $name) {
                if (in_array($name, $names, true)) {
                    continue;
                }

                $names[] = $name;
            }
        }

        sort($names);

        return $names;
    }
}
