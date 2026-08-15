<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireExceptionChaining;

use DomainException;
use RuntimeException;

final class WithUnchainedThrow
{
    public function withUnreferencedCaughtVariable(): int
    {
        try {
            return 1;
        } catch (RuntimeException $exception) {
            throw new DomainException('wrapped');
        }
    }

    public function withNonBindingCatch(): int
    {
        try {
            return 1;
        } catch (RuntimeException) {
            throw new DomainException('wrapped');
        }
    }
}
