<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard;

use PhpAiToolkit\ScopeGuard\ScopeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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
