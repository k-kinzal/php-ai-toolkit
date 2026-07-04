<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard;

use PhpAiToolkit\LocGuard\LocGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(LocGuardException::class)]
final class LocGuardExceptionTest extends TestCase
{
    public function testIsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);

        throw new LocGuardException('Failed.');
    }
}
