<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidGenericThrowsTag;

use Exception;
use RuntimeException;
use Throwable;

final class WithGenericThrowsTag
{
    /**
     * @throws Exception
     */
    public function withGenericException(): void
    {
        throw new RuntimeException('failed');
    }

    /**
     * @throws Throwable
     */
    public function withGenericThrowable(): void
    {
        throw new RuntimeException('failed');
    }

    /**
     * @throws Exception|RuntimeException
     */
    public function withGenericUnionMember(): void
    {
        throw new RuntimeException('failed');
    }
}
