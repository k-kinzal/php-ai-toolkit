<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Scope;

use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser
 */
#[CoversClass(VisibilityTagParser::class)]
final class VisibilityTagParserTest extends TestCase
{
    public function testValuesReadsKeywordFromMultiLineComment(): void
    {
        self::assertSame(['namespace'], (new VisibilityTagParser())->values("/**\n * @visibility namespace\n */"));
    }

    public function testValuesReadsEveryDeclaredScope(): void
    {
        self::assertSame(['root', 'App\\Domain'], (new VisibilityTagParser())->values("/**\n * @visibility root\n * @visibility App\\Domain\n */"));
    }

    public function testValuesReadsSingleLineComment(): void
    {
        self::assertSame(['root'], (new VisibilityTagParser())->values('/** @visibility root */'));
    }

    public function testValuesIgnoresTagNamedInProse(): void
    {
        self::assertSame([], (new VisibilityTagParser())->values("/**\n * Explains why an @visibility value cannot be honoured.\n */"));
    }

    public function testValuesReturnsNothingWithoutTag(): void
    {
        self::assertSame([], (new VisibilityTagParser())->values("/**\n * @throws RuntimeException\n */"));
    }

    public function testValuesReturnsNothingWithoutComment(): void
    {
        self::assertSame([], (new VisibilityTagParser())->values(null));
    }

    public function testValuesIgnoresTagWithoutScope(): void
    {
        self::assertSame([], (new VisibilityTagParser())->values("/**\n * @visibility\n */"));
    }
}
