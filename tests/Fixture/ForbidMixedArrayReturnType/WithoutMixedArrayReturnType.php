<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidMixedArrayReturnType;

final class WithoutMixedArrayReturnType
{
    /** @var array<string, mixed> */
    public array $values = [];

    /**
     * @param array<string, mixed> $values
     * @return array<string, bool|int|string>
     */
    public function withMixedInput(array $values): array
    {
        return [];
    }

    /**
     * @return list<mixed>
     */
    public function withMixedList(): array
    {
        return [];
    }

    /**
     * @return array{payload: mixed}
     */
    public function withMixedShapeField(): array
    {
        return ['payload' => null];
    }

    /**
     * @return iterable<string, mixed>
     */
    public function withMixedIterable(): iterable
    {
        return [];
    }
}
