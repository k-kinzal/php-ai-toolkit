<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Toolkit\ScopeGuard\ScopeGuardException;

/**
 * @covers \Toolkit\ScopeGuard\ScopeGuardException
 */
#[CoversClass(ScopeGuardException::class)]
final class ScopeGuardExceptionTest extends TestCase
{
    /**
     * @throws ScopeGuardException
     */
    public function testIsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);

        throw new ScopeGuardException('Failed.');
    }
}
