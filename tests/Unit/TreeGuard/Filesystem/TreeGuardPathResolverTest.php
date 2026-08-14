<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Filesystem;

use PhpAiToolkit\TreeGuard\Filesystem\TreeGuardPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TreeGuardPathResolver::class)]
final class TreeGuardPathResolverTest extends TestCase
{
    public function testAbsoluteJoinsRootAndRelativePath(): void
    {
        self::assertSame('/project/src', (new TreeGuardPathResolver())->absolute('/project', 'src'));
    }

    public function testAbsoluteKeepsAbsolutePath(): void
    {
        self::assertSame('/other/src', (new TreeGuardPathResolver())->absolute('/project', '/other/src/'));
    }

    public function testRelativeStripsRootPrefix(): void
    {
        self::assertSame('src/A', (new TreeGuardPathResolver())->relative('/project', '/project/src/A'));
    }

    public function testRelativeKeepsPathOutsideRoot(): void
    {
        self::assertSame('/other/src', (new TreeGuardPathResolver())->relative('/project', '/other/src'));
    }
}
