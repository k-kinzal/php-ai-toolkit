<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidMixedArrayReturnType;

final class WithMixedArrayReturnType
{
    /**
     * @return array<string, mixed>
     */
    public function withStringKeys(): array
    {
        return [];
    }

    /**
     * @return array<mixed>
     */
    public function withImplicitKeys(): array
    {
        return [];
    }

    /**
     * @return array<int, mixed>|null
     */
    public function withNullableArray(): ?array
    {
        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function withNestedArray(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     * @phpstan-return non-empty-array<string, mixed>
     */
    public function withPhpStanReturn(): array
    {
        return ['value' => 'value'];
    }

    /**
     * @psalm-return array<array-key, mixed>
     */
    public function withPsalmReturn(): array
    {
        return [];
    }
}

/**
 * @return array<string, mixed>
 */
function mixedArrayFunction(): array
{
    return [];
}
