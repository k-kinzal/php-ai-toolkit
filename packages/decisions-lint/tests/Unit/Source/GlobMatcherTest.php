<?php

declare(strict_types=1);

namespace Tests\Unit\Source;

use DecisionsLint\Source\GlobMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Source\GlobMatcher
 */
#[CoversClass(GlobMatcher::class)]
final class GlobMatcherTest extends TestCase
{
    /**
     * @dataProvider providerMatches
     */
    #[DataProvider('providerMatches')]
    public function testMatchesSupportsSegmentsDoubleStarsAndDirectoryPrefixes(string $pattern, string $path, bool $expected): void
    {
        self::assertSame($expected, (new GlobMatcher())->matches($pattern, $path));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function providerMatches(): iterable
    {
        yield 'directory prefix' => ['src', 'src/App/Foo.php', true];
        yield 'exact file' => ['src/Foo.php', 'src/Foo.php', true];
        yield 'star within a segment' => ['src/*.php', 'src/Foo.php', true];
        yield 'star does not cross segments' => ['src/*.php', 'src/App/Foo.php', false];
        yield 'double star crosses segments' => ['src/**/*Analyzer.php', 'src/App/Deep/FooAnalyzer.php', true];
        yield 'double star matches zero segments' => ['src/**/*.php', 'src/Foo.php', true];
        yield 'question mark' => ['src/?oo.php', 'src/Foo.php', true];
        yield 'dot is everything' => ['.', 'anything/at/all.txt', true];
        yield 'other directory' => ['tests', 'src/Foo.php', false];
        yield 'trailing double star' => ['src/Generated/**', 'src/Generated/A/B.php', true];
    }

    public function testMatchesAnyChecksEveryPattern(): void
    {
        $matcher = new GlobMatcher();
        self::assertTrue($matcher->matchesAny(['tests', 'src/**/*.php'], 'src/A.php'));
        self::assertFalse($matcher->matchesAny(['tests'], 'src/A.php'));
        self::assertFalse($matcher->matchesAny([], 'src/A.php'));
    }

    public function testBaseReturnsTheLeadingDirectoryWithoutWildcards(): void
    {
        $matcher = new GlobMatcher();
        self::assertSame('src/App', $matcher->base('src/App/**/*.php'));
        self::assertSame('src', $matcher->base('src'));
        self::assertSame('', $matcher->base('**/*.php'));
    }

    public function testRegexAnchorsThePattern(): void
    {
        self::assertSame('#^src/[^/]*\.php$#', (new GlobMatcher())->regex('src/*.php'));
        self::assertSame('#^(?:.*/)?[^/]\.php$#', (new GlobMatcher())->regex('**/?.php'));
    }
}
