<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidEmptyCatch;

use RuntimeException;
use Throwable;

final class WithEmptyCatch
{
    public function withBoundVariable(): int
    {
        try {
            return 1;
        } catch (RuntimeException $exception) {
        }

        return 0;
    }

    public function withoutBoundVariable(): int
    {
        try {
            return 1;
        } catch (Throwable) {
        }

        return 0;
    }
}
