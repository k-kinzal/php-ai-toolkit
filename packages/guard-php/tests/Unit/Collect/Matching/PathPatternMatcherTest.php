<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Matching;

use Guard\Collect\Matching\PathPatternMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Matching\PathPatternMatcher
 */
#[CoversClass(PathPatternMatcher::class)]
final class PathPatternMatcherTest extends TestCase
{
    public function testCanMatchBelowStopsAtTheMaximumPatternDepth(): void
    {
        $matcher = new PathPatternMatcher();
        self::assertTrue($matcher->canMatchBelow('docs/*/index.md', 'docs/topic'));
        self::assertFalse($matcher->canMatchBelow('docs/*/index.md', 'docs/topic/deep'));
        self::assertTrue($matcher->canMatchBelow('docs/**/*.md', 'docs/topic/deep'));
    }

    public function testStatesRetainsRecursiveAndCompletedAlternatives(): void
    {
        self::assertSame([1 => true, 2 => true], (new PathPatternMatcher())->states('src/**', 'src/deep'));
    }
    public function testMatchesUsesSegmentAwareStarsAndRecursiveDoubleStars(): void
    {
        $matcher = new PathPatternMatcher();

        self::assertTrue($matcher->matches('src/*.php', 'src/Example.php'));
        self::assertFalse($matcher->matches('src/*.php', 'src/Nested/Example.php'));
        self::assertTrue($matcher->matches('src/**/*.php', 'src/Nested/Example.php'));
        self::assertTrue($matcher->matches('src/**/*.php', 'src/Example.php'));
    }
}
