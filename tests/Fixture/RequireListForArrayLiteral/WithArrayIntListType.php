<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireListForArrayLiteral;

final class WithArrayIntListType
{
    /** @var array<int, string> */
    public array $names = ['foo', 'bar'];

    /** @phpstan-var array<int, non-empty-string> */
    public array $phpstanNames = ['foo'];

    /** @var array<int, string> $first */
    public array $first = ['first'], $second = ['second'];

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        return ['foo', 'bar'];
    }

    /**
     * @return array<int, non-empty-string>|null
     */
    public function nullableNames(bool $found): ?array
    {
        if (!$found) {
            return null;
        }

        return ['foo'];
    }

    /**
     * @psalm-return array<int, string>
     */
    public function psalmNames(): array
    {
        return ['foo'];
    }
}

/**
 * @return array<int, string>
 */
function arrayIntNames(): array
{
    return ['foo', 'bar'];
}
