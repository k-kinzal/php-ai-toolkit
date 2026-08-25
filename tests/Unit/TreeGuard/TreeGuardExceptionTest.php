<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard;

use PhpAiToolkit\TreeGuard\TreeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \PhpAiToolkit\TreeGuard\TreeGuardException
 */
#[CoversClass(TreeGuardException::class)]
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
