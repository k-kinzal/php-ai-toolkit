<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Matching;

use Guard\Collect\Matching\ScopeMatcher;
use Guard\Input\Entry;
use Guard\Input\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Matching\ScopeMatcher
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Path
 * @uses \Guard\Input\PathPatternMatcher
 * @uses \Guard\Input\Scope
 */
#[CoversClass(ScopeMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Path::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\PathPatternMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Scope::class)]
final class ScopeMatcherTest extends TestCase
{
    public function testContainsIntersectsIncludesAndExclusionsWithNormalizedPaths(): void
    {
        $matcher = new ScopeMatcher('/root', '/root', new Scope(['src', 'docs/**/*.md'], ['src/generated']));
        self::assertTrue($matcher->contains('./src//A.php'));
        self::assertTrue($matcher->contains('docs/nested/A.md'));
        self::assertFalse($matcher->contains('docs/A.php'));
        self::assertFalse($matcher->contains('src/generated/A.php'));
        self::assertFalse($matcher->contains('/outside/A.php'));
    }

    public function testMayContainRejectsImpossibleDescendantsWithoutFileMetadata(): void
    {
        $matcher = new ScopeMatcher('/root', '/root', new Scope(['src/*/Only.php'], ['src/generated']));
        self::assertTrue($matcher->mayContain('src/lib'));
        self::assertFalse($matcher->mayContain('src/lib/deep'));
        self::assertFalse($matcher->mayContain('src/generated'));
        self::assertFalse($matcher->mayContain('vendor'));
    }

    public function testRootsStartsAtTheDeeperCompatiblePrefix(): void
    {
        $matcher = new ScopeMatcher('/root', '/root', new Scope(['src/app/**', 'docs', 'README.md']));
        self::assertSame(['src/app'], $matcher->roots('src'));
        self::assertSame(['src/app/Domain'], $matcher->roots('src/app/Domain'));
        self::assertSame([], $matcher->roots('vendor'));
        self::assertSame(['src/app', 'docs', 'README.md'], $matcher->roots('.'));
    }

    public function testRelativeNormalizesAbsoluteInProjectPathsAndDotSegments(): void
    {
        $matcher = new ScopeMatcher('/root', '/root', new Scope());
        self::assertSame('src/A.php', $matcher->relative('/root/src/./A.php'));
        self::assertSame('src/A.php', $matcher->relative('./src//A.php'));
    }

    public function testPrefixStopsAtTheFirstWildcardSegment(): void
    {
        $matcher = new ScopeMatcher('/root', '/root', new Scope());
        self::assertSame('docs', $matcher->prefix('docs/*/reference.md'));
        self::assertSame('', $matcher->prefix('**/*.xml'));
    }

    public function testExcludesPrunesDescendantsOfMatchedDirectories(): void
    {
        $matcher = new ScopeMatcher('/root', '/root', new Scope(['**'], ['**/generated', 'vendor/**']));
        self::assertTrue($matcher->excludes('src/generated/A.php'));
        self::assertTrue($matcher->excludes('vendor'));
        self::assertFalse($matcher->excludes('src/A.php'));
    }

    public function testAcceptsRejectsSymlinksAndPhysicalTargetsOutsideTheScope(): void
    {
        $matcher = new ScopeMatcher('/root', '/physical/root', new Scope(['src']));
        self::assertTrue($matcher->accepts('src/A.php', new Entry(false, true, false, '/physical/root/src/A.php')));
        self::assertFalse($matcher->accepts('src/link.php', new Entry(false, true, true, '/outside/A.php')));
        self::assertFalse($matcher->accepts('src/alias/A.php', new Entry(false, true, false, '/physical/root/vendor/A.php')));
        self::assertFalse($matcher->accepts('src/alias/A.php', new Entry(false, true, false, '/outside/A.php')));
    }
}
