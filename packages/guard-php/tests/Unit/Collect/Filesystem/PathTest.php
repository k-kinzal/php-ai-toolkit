<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Filesystem\Path
 */
#[CoversClass(\Guard\Collect\Filesystem\Path::class)]
final class PathTest extends TestCase
{
    public function testAbsoluteResolvesConfiguredRootsAndAbsoluteFiles(): void
    {
        $paths = new \Guard\Collect\Filesystem\Path();
        self::assertSame('/project/src', $paths->absolute('/project', 'src'));
        self::assertSame('/other', $paths->absolute('/project', '/other'));
    }
    public function testNormalizeDeduplicatesOverlappingRootSpellings(): void
    {
        self::assertSame('/project/src', (new \Guard\Collect\Filesystem\Path())->normalize('/project/./src/sub/..'));
    }
    public function testRelativeKeepsOutsidePathsDistinctFromProjectPaths(): void
    {
        $paths = new \Guard\Collect\Filesystem\Path();
        self::assertSame('.', $paths->relative('/project', '/project'));
        self::assertSame('src/A.php', $paths->relative('/project', '/project/src/A.php'));
        self::assertSame('/project-other/A.php', $paths->relative('/project', '/project-other/A.php'));
    }
    public function testChildUsesTheExistingRootNotation(): void
    {
        self::assertSame('src', (new \Guard\Collect\Filesystem\Path())->child('.', 'src'));
    }
    public function testDescendantPrefixDoesNotAddADotSegment(): void
    {
        self::assertSame('', (new \Guard\Collect\Filesystem\Path())->descendantPrefix('.'));
        self::assertSame('src/', (new \Guard\Collect\Filesystem\Path())->descendantPrefix('src'));
    }
    public function testSpellingPreservesParentSegmentsForDocumentCompatibility(): void
    {
        self::assertSame('../README.md', (new \Guard\Collect\Filesystem\Path())->spelling('./../README.md/'));
    }
}
