<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireThrowsTagOnDirectThrow;

use BadMethodCallException;
use LogicException;
use RuntimeException;

final class WithDeclaredOrCaughtThrow
{
    /**
     * @throws RuntimeException
     */
    public function withTag(): void
    {
        throw new RuntimeException('failed');
    }

    public function withMatchingCatch(): int
    {
        try {
            throw new RuntimeException('failed');
        } catch (RuntimeException $exception) {
            return $exception->getCode();
        }
    }

    public function withParentCatch(): int
    {
        try {
            throw new BadMethodCallException('failed');
        } catch (LogicException $exception) {
            return $exception->getCode();
        }
    }

    /**
     * @throws LogicException
     */
    public function withDeclaredRethrow(): int
    {
        try {
            return 1;
        } catch (LogicException $exception) {
            throw $exception;
        }
    }

    public function withThrowInClosure(): callable
    {
        return static function (): void {
            throw new RuntimeException('failed');
        };
    }
}
