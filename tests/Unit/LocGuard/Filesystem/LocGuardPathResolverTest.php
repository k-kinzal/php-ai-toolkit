<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Filesystem\LocGuardPathResolver;

/**
 * @covers \Toolkit\LocGuard\Filesystem\LocGuardPathResolver
 */
#[CoversClass(LocGuardPathResolver::class)]
final class LocGuardPathResolverTest extends TestCase
{
    public function testAbsoluteReturnsAbsoluteConfiguredPath(): void
    {
        self::assertSame('/tmp/project/src', (new LocGuardPathResolver())->absolute('/tmp/project', 'src'));
        self::assertSame('/var/project/src', (new LocGuardPathResolver())->absolute('/tmp/project', '/var/project/src/'));
    }

    public function testRelativeReturnsProjectRelativePath(): void
    {
        self::assertSame('src/Example.php', (new LocGuardPathResolver())->relative('/tmp/project', '/tmp/project/src/Example.php'));
    }
}
