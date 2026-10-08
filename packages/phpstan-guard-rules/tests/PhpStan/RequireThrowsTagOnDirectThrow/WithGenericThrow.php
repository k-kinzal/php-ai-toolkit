<?php

declare(strict_types=1);

namespace Tests\PhpStan\RequireThrowsTagOnDirectThrow;

use Exception;

final class WithGenericThrow
{
    public function throwsException(): void
    {
        throw new Exception('failed');
    }
}
