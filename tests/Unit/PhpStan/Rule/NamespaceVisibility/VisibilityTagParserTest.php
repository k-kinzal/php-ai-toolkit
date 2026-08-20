<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\NamespaceVisibility;

use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityTagParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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

    public function testValuesStopsAtCommentTerminator(): void
    {
        self::assertSame(['root'], (new VisibilityTagParser())->values('/** @visibility root */'));
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
