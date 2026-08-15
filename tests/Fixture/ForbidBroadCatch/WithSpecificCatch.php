<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidBroadCatch;

use JsonException;
use RuntimeException;

final class WithSpecificCatch
{
    public function catchesRuntimeException(): int
    {
        try {
            return 1;
        } catch (RuntimeException $exception) {
            return $exception->getCode();
        }
    }

    public function catchesJsonException(): int
    {
        try {
            return 1;
        } catch (JsonException $exception) {
            return $exception->getCode();
        }
    }
}
