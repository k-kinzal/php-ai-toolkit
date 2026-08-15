<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireThrowsTagOnDirectThrow;

use LogicException;
use RuntimeException;

final class WithUndeclaredThrow
{
    public function withoutTag(): void
    {
        throw new RuntimeException('failed');
    }

    /**
     * @throws LogicException
     */
    public function withWrongTag(): void
    {
        throw new RuntimeException('failed');
    }

    public function withMismatchedCatch(): int
    {
        try {
            throw new RuntimeException('failed');
        } catch (LogicException $exception) {
            return $exception->getCode();
        }
    }

    public function withUndeclaredRethrow(): int
    {
        try {
            return 1;
        } catch (RuntimeException $exception) {
            throw $exception;
        }
    }
}
