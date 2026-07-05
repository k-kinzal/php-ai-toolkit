<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\ArrowExpressionBoundary;
use PhpAiToolkit\LocGuard\Analysis\FunctionBodyLocator;
use PhpAiToolkit\LocGuard\Analysis\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FunctionBodyLocator::class)]
#[UsesClass(ArrowExpressionBoundary::class)]
#[UsesClass(PhpTokenNavigator::class)]
final class FunctionBodyLocatorTest extends TestCase
{
    public function testBlockBodyStartReturnsOpeningBraceIndex(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php function run(): void {}', TOKEN_PARSE));

        self::assertSame(10, (new FunctionBodyLocator())->blockBodyStart($tokens, 1));
    }

    public function testBlockBodyStartReturnsNullForBodylessMethod(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php interface Contract { public function run(): void; }', TOKEN_PARSE));

        self::assertNull((new FunctionBodyLocator())->blockBodyStart($tokens, 9));
    }

    public function testBlockBodyEndReturnsClosingBraceIndex(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php function run(): void {}', TOKEN_PARSE));

        self::assertSame(11, (new FunctionBodyLocator())->blockBodyEnd($tokens, 10));
    }

    public function testArrowBodyStartReturnsDoubleArrowIndex(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php $value = fn (int $n): int => $n;', TOKEN_PARSE));

        self::assertSame(16, (new FunctionBodyLocator())->arrowBodyStart($tokens, 5));
    }

    public function testArrowBodyEndReturnsExpressionEndIndex(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php $value = fn (int $n): int => $n;', TOKEN_PARSE));

        self::assertSame(19, (new FunctionBodyLocator())->arrowBodyEnd($tokens, 16));
    }
}
