<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Toolkit\DocGuard\DocGuardException;

/**
 * @coversNothing
 */
#[CoversNothing]
final class DocGuardExceptionTest extends TestCase
{
    /**
     * @throws DocGuardException
     */
    public function testIsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);

        throw new DocGuardException('Failed.');
    }
}
