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
    public function testMatchesUsesSegmentAwareStarsAndRecursiveDoubleStars(): void
    {
        $matcher = new PathPatternMatcher();

        self::assertTrue($matcher->matches('src/*.php', 'src/Example.php'));
        self::assertFalse($matcher->matches('src/*.php', 'src/Nested/Example.php'));
        self::assertTrue($matcher->matches('src/**/*.php', 'src/Nested/Example.php'));
        self::assertTrue($matcher->matches('src/**/*.php', 'src/Example.php'));
    }
}
