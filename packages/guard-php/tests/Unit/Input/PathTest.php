<?php

declare(strict_types=1);

namespace Tests\Unit\Input;

use Guard\Input\Path;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Input\Path
 */
#[CoversClass(Path::class)]
final class PathTest extends TestCase
{
    public function testAbsoluteResolvesConfiguredRootsAndAbsoluteFiles(): void
    {
        $paths = new Path();
        self::assertSame('/project/src', $paths->absolute('/project', 'src'));
        self::assertSame('/other', $paths->absolute('/project', '/other'));
    }
    public function testNormalizeDeduplicatesOverlappingRootSpellings(): void
    {
        self::assertSame('/project/src', (new Path())->normalize('/project/./src/sub/..'));
    }
    public function testRelativeKeepsOutsidePathsDistinctFromProjectPaths(): void
    {
        $paths = new Path();
        self::assertSame('.', $paths->relative('/project', '/project'));
        self::assertSame('src/A.php', $paths->relative('/project', '/project/src/A.php'));
        self::assertSame('/project-other/A.php', $paths->relative('/project', '/project-other/A.php'));
    }
    public function testChildUsesTheExistingRootNotation(): void
    {
        self::assertSame('src', (new Path())->child('.', 'src'));
    }
    public function testDescendantPrefixDoesNotAddADotSegment(): void
    {
        self::assertSame('', (new Path())->descendantPrefix('.'));
        self::assertSame('src/', (new Path())->descendantPrefix('src'));
    }
    public function testSpellingPreservesParentSegmentsForDocumentCompatibility(): void
    {
        self::assertSame('../README.md', (new Path())->spelling('./../README.md/'));
    }
}
