<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Markdown\Filesystem;

use Guard\Collect\Markdown\Filesystem\PathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Markdown\Filesystem\PathResolver
 */
#[CoversClass(PathResolver::class)]
final class PathResolverTest extends TestCase
{
    public function testResolveJoinsRelativePaths(): void
    {
        self::assertSame('/project/docs/guide.md', (new PathResolver())->resolve('/project/', 'docs/guide.md'));
        self::assertSame('/elsewhere/README.md', (new PathResolver())->resolve('/project', '/elsewhere/README.md'));
    }

    public function testNormalizeRemovesRedundantSegments(): void
    {
        self::assertSame('docs/guide.md', (new PathResolver())->normalize('./././docs//guide.md'));
        self::assertSame('docs', (new PathResolver())->normalize('docs/'));
        self::assertSame('.', (new PathResolver())->normalize('./'));
        self::assertSame('../../README.md', (new PathResolver())->normalize('../../README.md'));
        self::assertSame('/', (new PathResolver())->normalize('/'));
    }
}
