<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidGenericThrowsTag;

use JsonException;
use RuntimeException;

final class WithConcreteThrowsTag
{
    /**
     * @throws RuntimeException
     */
    public function withConcreteTag(): void
    {
        throw new RuntimeException('failed');
    }

    /**
     * @throws JsonException|RuntimeException
     */
    public function withConcreteUnionTag(): void
    {
        throw new RuntimeException('failed');
    }

    public function withoutTag(): int
    {
        return 1;
    }
}
