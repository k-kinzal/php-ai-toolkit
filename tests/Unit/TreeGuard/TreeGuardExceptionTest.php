<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Toolkit\TreeGuard\TreeGuardException;

/**
 * @coversNothing
 */
#[CoversNothing]
final class TreeGuardExceptionTest extends TestCase
{
    /**
     * @throws TreeGuardException
     */
    public function testIsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);

        throw new TreeGuardException('Failed.');
    }
}
