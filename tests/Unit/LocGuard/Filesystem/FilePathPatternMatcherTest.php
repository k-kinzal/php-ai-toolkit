<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Filesystem\FilePathPatternMatcher;

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
