<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidBroadCatch;

use Throwable;

final class WithAllowedBroadCatch
{
    public function run(): int
    {
        try {
            return 1;
        } catch (Throwable $exception) {
            return $exception->getCode();
        }
    }
}
