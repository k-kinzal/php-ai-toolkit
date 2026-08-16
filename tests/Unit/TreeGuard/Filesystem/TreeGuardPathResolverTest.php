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

    public function testAbsoluteResolvesProjectRootPath(): void
    {
        self::assertSame('/project', (new TreeGuardPathResolver())->absolute('/project', '.'));
        self::assertSame('/project', (new TreeGuardPathResolver())->absolute('/project', './'));
    }

    public function testRelativeReturnsDotForProjectRoot(): void
    {
        self::assertSame('.', (new TreeGuardPathResolver())->relative('/project', '/project'));
    }

    public function testChildJoinsNameOntoDirectory(): void
    {
        self::assertSame('src/A', (new TreeGuardPathResolver())->child('src', 'A'));
        self::assertSame('src', (new TreeGuardPathResolver())->child('.', 'src'));
    }

    public function testDescendantPrefixIsEmptyForProjectRoot(): void
    {
        self::assertSame('src/', (new TreeGuardPathResolver())->descendantPrefix('src'));
        self::assertSame('', (new TreeGuardPathResolver())->descendantPrefix('.'));
    }
}
