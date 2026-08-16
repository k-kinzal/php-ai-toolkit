<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

use PhpAiToolkit\TreeGuard\Analysis\DirectoryPatternMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DirectoryPatternMatcher::class)]
final class DirectoryPatternMatcherTest extends TestCase
{
    public function testMatchesExactPathOnly(): void
    {
        self::assertTrue((new DirectoryPatternMatcher())->matches('src', 'src'));
        self::assertFalse((new DirectoryPatternMatcher())->matches('src', 'src/A'));
        self::assertFalse((new DirectoryPatternMatcher())->matches('src', 'source'));
    }

    public function testMatchesSingleStarWithinOneSegment(): void
    {
        self::assertTrue((new DirectoryPatternMatcher())->matches('src/*', 'src/A'));
        self::assertFalse((new DirectoryPatternMatcher())->matches('src/*', 'src'));
        self::assertFalse((new DirectoryPatternMatcher())->matches('src/*', 'src/A/B'));
    }

    public function testMatchesDoubleStarIncludingBaseDirectory(): void
    {
        self::assertTrue((new DirectoryPatternMatcher())->matches('src/**', 'src'));
        self::assertTrue((new DirectoryPatternMatcher())->matches('src/**', 'src/A'));
        self::assertTrue((new DirectoryPatternMatcher())->matches('src/**', 'src/A/B/C'));
        self::assertFalse((new DirectoryPatternMatcher())->matches('src/**', 'tests'));
    }

    public function testMatchesLeadingDoubleStarAtAnyDepth(): void
    {
        self::assertTrue((new DirectoryPatternMatcher())->matches('**/Rule', 'Rule'));
        self::assertTrue((new DirectoryPatternMatcher())->matches('**/Rule', 'src/PhpStan/Rule'));
        self::assertFalse((new DirectoryPatternMatcher())->matches('**/Rule', 'src/Rule/Nested'));
    }

    public function testMatchesDoubleStarBetweenSegments(): void
    {
        self::assertTrue((new DirectoryPatternMatcher())->matches('src/**/Rule', 'src/Rule'));
        self::assertTrue((new DirectoryPatternMatcher())->matches('src/**/Rule', 'src/A/B/Rule'));
        self::assertFalse((new DirectoryPatternMatcher())->matches('src/**/Rule', 'tests/A/Rule'));
    }

    public function testMatchesIsAnchoredAtBothEnds(): void
    {
        self::assertFalse((new DirectoryPatternMatcher())->matches('PhpStan/Rule', 'src/PhpStan/Rule'));
        self::assertFalse((new DirectoryPatternMatcher())->matches('src/PhpStan', 'src/PhpStan/Rule'));
    }

    public function testMatchesProjectRootWithDotAndDoubleStarOnly(): void
    {
        self::assertTrue((new DirectoryPatternMatcher())->matches('.', '.'));
        self::assertTrue((new DirectoryPatternMatcher())->matches('**', '.'));
        self::assertFalse((new DirectoryPatternMatcher())->matches('*', '.'));
        self::assertFalse((new DirectoryPatternMatcher())->matches('src', '.'));
        self::assertFalse((new DirectoryPatternMatcher())->matches('.', 'src'));
    }

    public function testMatchesDirectoriesBelowProjectRoot(): void
    {
        self::assertTrue((new DirectoryPatternMatcher())->matches('**', '.github/workflows'));
        self::assertTrue((new DirectoryPatternMatcher())->matches('*', 'scripts'));
        self::assertTrue((new DirectoryPatternMatcher())->matches('src/**', 'src/PhpStan'));
    }

    public function testMatchesGlobCharactersPerSegment(): void
    {
        self::assertTrue((new DirectoryPatternMatcher())->matches('skills/setup-*', 'skills/setup-toolkit-phpstan'));
        self::assertTrue((new DirectoryPatternMatcher())->matches('src/[A-Z]*', 'src/Analysis'));
        self::assertFalse((new DirectoryPatternMatcher())->matches('src/[A-Z]*', 'src/analysis'));
    }
}
