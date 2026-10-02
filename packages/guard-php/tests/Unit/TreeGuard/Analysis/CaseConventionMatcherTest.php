<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Analysis\CaseConventionMatcher;

/**
 * @covers \Toolkit\TreeGuard\Analysis\CaseConventionMatcher
 */
#[CoversClass(CaseConventionMatcher::class)]
final class CaseConventionMatcherTest extends TestCase
{
    public function testMatchesPascalConvention(): void
    {
        self::assertTrue((new CaseConventionMatcher())->matches('pascal', 'AiReporter'));
        self::assertFalse((new CaseConventionMatcher())->matches('pascal', 'aiReporter'));
        self::assertFalse((new CaseConventionMatcher())->matches('pascal', 'Ai_Reporter'));
    }

    public function testMatchesCamelConvention(): void
    {
        self::assertTrue((new CaseConventionMatcher())->matches('camel', 'aiReporter'));
        self::assertFalse((new CaseConventionMatcher())->matches('camel', 'AiReporter'));
    }

    public function testMatchesSnakeConvention(): void
    {
        self::assertTrue((new CaseConventionMatcher())->matches('snake', 'ai_reporter_v2'));
        self::assertFalse((new CaseConventionMatcher())->matches('snake', 'ai-reporter'));
        self::assertFalse((new CaseConventionMatcher())->matches('snake', '_reporter'));
    }

    public function testMatchesKebabConvention(): void
    {
        self::assertTrue((new CaseConventionMatcher())->matches('kebab', 'setup-toolkit-phpstan'));
        self::assertFalse((new CaseConventionMatcher())->matches('kebab', 'setup_toolkit'));
        self::assertFalse((new CaseConventionMatcher())->matches('kebab', 'setup--toolkit'));
    }

    public function testMatchesReturnsFalseForUnknownConvention(): void
    {
        self::assertFalse((new CaseConventionMatcher())->matches('upper', 'ABC'));
    }

    public function testStemReturnsNameBeforeFirstDot(): void
    {
        self::assertSame('AiReporter', (new CaseConventionMatcher())->stem('AiReporter.php'));
        self::assertSame('archive', (new CaseConventionMatcher())->stem('archive.tar.gz'));
    }

    public function testStemReturnsWholeNameWithoutDot(): void
    {
        self::assertSame('Makefile', (new CaseConventionMatcher())->stem('Makefile'));
    }

    public function testStemReturnsEmptyStringForDotfiles(): void
    {
        self::assertSame('', (new CaseConventionMatcher())->stem('.gitignore'));
    }
}
