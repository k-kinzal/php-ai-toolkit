<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Tree\Filesystem;

use Guard\Collect\Tree\Filesystem\PathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Tree\Filesystem\PathResolver
 */
#[CoversClass(PathResolver::class)]
final class PathResolverTest extends TestCase
{
    public function testAbsoluteJoinsRootAndRelativePath(): void
    {
        self::assertSame('/project/src', (new PathResolver())->absolute('/project', 'src'));
    }

    public function testAbsoluteKeepsAbsolutePath(): void
    {
        self::assertSame('/other/src', (new PathResolver())->absolute('/project', '/other/src/'));
    }

    public function testRelativeStripsRootPrefix(): void
    {
        self::assertSame('src/A', (new PathResolver())->relative('/project', '/project/src/A'));
    }

    public function testRelativeKeepsPathOutsideRoot(): void
    {
        self::assertSame('/other/src', (new PathResolver())->relative('/project', '/other/src'));
    }

    public function testAbsoluteResolvesProjectRootPath(): void
    {
        self::assertSame('/project', (new PathResolver())->absolute('/project', '.'));
        self::assertSame('/project', (new PathResolver())->absolute('/project', './'));
    }

    public function testRelativeReturnsDotForProjectRoot(): void
    {
        self::assertSame('.', (new PathResolver())->relative('/project', '/project'));
    }

    public function testChildJoinsNameOntoDirectory(): void
    {
        self::assertSame('src/A', (new PathResolver())->child('src', 'A'));
        self::assertSame('src', (new PathResolver())->child('.', 'src'));
    }

    public function testDescendantPrefixIsEmptyForProjectRoot(): void
    {
        self::assertSame('src/', (new PathResolver())->descendantPrefix('src'));
        self::assertSame('', (new PathResolver())->descendantPrefix('.'));
    }
}
