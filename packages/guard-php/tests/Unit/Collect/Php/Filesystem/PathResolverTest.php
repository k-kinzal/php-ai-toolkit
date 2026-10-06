<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Php\Filesystem;

use Guard\Collect\Php\Filesystem\PathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Php\Filesystem\PathResolver
 */
#[CoversClass(PathResolver::class)]
final class PathResolverTest extends TestCase
{
    public function testAbsoluteReturnsAbsoluteConfiguredPath(): void
    {
        self::assertSame('/tmp/project/src', (new PathResolver())->absolute('/tmp/project', 'src'));
        self::assertSame('/var/project/src', (new PathResolver())->absolute('/tmp/project', '/var/project/src/'));
    }

    public function testRelativeReturnsProjectRelativePath(): void
    {
        self::assertSame('src/Example.php', (new PathResolver())->relative('/tmp/project', '/tmp/project/src/Example.php'));
    }
}
