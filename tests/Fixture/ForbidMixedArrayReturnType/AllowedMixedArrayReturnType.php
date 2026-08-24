<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidMixedArrayReturnType;

final class AllowedMixedArrayReturnType
{
    /**
     * @return array<string, mixed>
     */
    public function boundaryValues(): array
    {
        return [];
    }
}
