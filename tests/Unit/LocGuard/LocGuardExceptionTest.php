<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\LocGuardException
 */
#[CoversClass(LocGuardException::class)]
final class LocGuardExceptionTest extends TestCase
{
    /**
     * @throws LocGuardException
     */
    public function testIsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);

        throw new LocGuardException('Failed.');
    }
}
