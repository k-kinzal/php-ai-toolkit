<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Filesystem;

use PhpAiToolkit\ScopeGuard\Filesystem\ScopeGuardPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScopeGuardPathResolver::class)]
final class ScopeGuardPathResolverTest extends TestCase
{
    public function testAbsoluteJoinsARelativePathToTheRoot(): void
    {
        self::assertSame('/project/src', (new ScopeGuardPathResolver())->absolute('/project', 'src'));
    }

    public function testAbsoluteKeepsAnAbsolutePath(): void
    {
        self::assertSame('/elsewhere/src', (new ScopeGuardPathResolver())->absolute('/project', '/elsewhere/src/'));
    }

    public function testRelativeStripsTheRootPrefix(): void
    {
        self::assertSame('src/Order.php', (new ScopeGuardPathResolver())->relative('/project', '/project/src/Order.php'));
    }

    public function testRelativeKeepsAPathOutsideTheRoot(): void
    {
        self::assertSame('/elsewhere/Order.php', (new ScopeGuardPathResolver())->relative('/project', '/elsewhere/Order.php'));
    }
}
