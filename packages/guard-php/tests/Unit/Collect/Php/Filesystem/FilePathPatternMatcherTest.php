<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Php\Filesystem;

use Guard\Collect\Php\Filesystem\FilePathPatternMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Php\Filesystem\FilePathPatternMatcher
 */
#[CoversClass(FilePathPatternMatcher::class)]
final class FilePathPatternMatcherTest extends TestCase
{
    public function testMatchesUsesSegmentAwareStarsAndRecursiveDoubleStars(): void
    {
        $matcher = new FilePathPatternMatcher();

        self::assertTrue($matcher->matches('src/*.php', 'src/Example.php'));
        self::assertFalse($matcher->matches('src/*.php', 'src/Nested/Example.php'));
        self::assertTrue($matcher->matches('src/**/*.php', 'src/Nested/Example.php'));
        self::assertTrue($matcher->matches('src/**/*.php', 'src/Example.php'));
    }
}
