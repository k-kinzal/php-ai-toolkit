<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExceptionChaining;

use DomainException;
use LogicException;
use RuntimeException;

final class WithChainedThrow
{
    public function withPreviousArgument(): int
    {
        try {
            return 1;
        } catch (RuntimeException $exception) {
            throw new DomainException('wrapped', 0, $exception);
        }
    }

    public function withPlainRethrow(): int
    {
        try {
            return 1;
        } catch (RuntimeException $exception) {
            throw $exception;
        }
    }

    public function withChainedNestedCatch(): int
    {
        try {
            return 1;
        } catch (RuntimeException $outer) {
            try {
                return 2;
            } catch (LogicException $inner) {
                throw new DomainException('wrapped', 0, $inner);
            }
        }
    }

    public function withThrowInClosure(): callable
    {
        try {
            return static function (): void {
                throw new DomainException('inside closure');
            };
        } catch (RuntimeException $exception) {
            return static function (): void {
                throw new DomainException('inside closure');
            };
        }
    }
}
