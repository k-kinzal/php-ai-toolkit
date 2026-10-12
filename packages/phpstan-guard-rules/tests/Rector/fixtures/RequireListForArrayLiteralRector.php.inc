<?php

namespace RectorFixtures\Lists;

class Example
{
    /** @var array<int, string> */
    public array $names = ['one', 'two'];
    /** @var array<int, string> */
    public array $keyed = [5 => 'five'];
    /** @var array<int, string> */
    public array $empty = [];
    /** @var array<int, string> */
    public array $first = ['first'], $second = [7 => 'seven'];
}

/**
 * Preserves input and description.
 * @param array<int, string> $input
 * @return array<int, array<int, string>>|null
 */
function values(array $input): ?array
{
    return rand(0, 1) ? null : null;
}

/**
 * @param array<int, string> $input
 * @return array<int, array<int, string>>|null
 */
function owned(array $input): ?array
{
    if ($input === []) {
        return null;
    }
    return [['nested']];
}

-----
<?php

namespace RectorFixtures\Lists;

class Example
{
    /**
     * @var list<string>
     */
    public array $names = ['one', 'two'];
    /** @var array<int, string> */
    public array $keyed = [5 => 'five'];
    /** @var array<int, string> */
    public array $empty = [];
    /** @var array<int, string> */
    public array $first = ['first'], $second = [7 => 'seven'];
}

/**
 * Preserves input and description.
 * @param array<int, string> $input
 * @return array<int, array<int, string>>|null
 */
function values(array $input): ?array
{
    return rand(0, 1) ? null : null;
}

/**
 * @param array<int, string> $input
 * @return (list<array<int, string>> | null)
 */
function owned(array $input): ?array
{
    if ($input === []) {
        return null;
    }
    return [['nested']];
}
