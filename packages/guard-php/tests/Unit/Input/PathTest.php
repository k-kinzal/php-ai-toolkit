<?php

declare(strict_types=1);

namespace Tests\Unit\Input;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Input\Path
 */
#[CoversClass(\Guard\Input\Path::class)]
final class PathTest extends TestCase
{
    public function testAbsoluteResolvesConfiguredRootsAndAbsoluteFiles(): void
    {
        $paths = new \Guard\Input\Path();
        self::assertSame('/project/src', $paths->absolute('/project', 'src'));
        self::assertSame('/other', $paths->absolute('/project', '/other'));
    }
    public function testNormalizeDeduplicatesOverlappingRootSpellings(): void
    {
        self::assertSame('/project/src', (new \Guard\Input\Path())->normalize('/project/./src/sub/..'));
    }
    public function testRelativeKeepsOutsidePathsDistinctFromProjectPaths(): void
    {
        $paths = new \Guard\Input\Path();
        self::assertSame('.', $paths->relative('/project', '/project'));
        self::assertSame('src/A.php', $paths->relative('/project', '/project/src/A.php'));
        self::assertSame('/project-other/A.php', $paths->relative('/project', '/project-other/A.php'));
    }
    public function testChildUsesTheExistingRootNotation(): void
    {
        self::assertSame('src', (new \Guard\Input\Path())->child('.', 'src'));
    }
    public function testDescendantPrefixDoesNotAddADotSegment(): void
    {
        self::assertSame('', (new \Guard\Input\Path())->descendantPrefix('.'));
        self::assertSame('src/', (new \Guard\Input\Path())->descendantPrefix('src'));
    }
    public function testSpellingPreservesParentSegmentsForDocumentCompatibility(): void
    {
        self::assertSame('../README.md', (new \Guard\Input\Path())->spelling('./../README.md/'));
    }
}
