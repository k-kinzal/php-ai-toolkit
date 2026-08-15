<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidEmptyCatch;

use RuntimeException;

final class WithHandledCatch
{
    public function run(): int
    {
        try {
            return 1;
        } catch (RuntimeException $exception) {
            return $exception->getCode();
        }
    }
}
