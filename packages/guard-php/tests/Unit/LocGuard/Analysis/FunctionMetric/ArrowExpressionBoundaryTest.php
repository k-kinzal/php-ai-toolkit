<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis\FunctionMetric;

use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary;

/**
 * @covers \Toolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary
 */
#[CoversClass(ArrowExpressionBoundary::class)]
final class ArrowExpressionBoundaryTest extends TestCase
{
    public function testEndStopsAtTopLevelDelimiter(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php $value = fn (int $n): int => $n;', TOKEN_PARSE));

        self::assertSame(19, (new ArrowExpressionBoundary())->end($tokens, 16));
    }

    public function testEndIgnoresNestedDelimiters(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php $value = fn (int $n): array => [($n), $n];', TOKEN_PARSE));

        self::assertSame(26, (new ArrowExpressionBoundary())->end($tokens, 16));
    }

    public function testIsTerminatorRequiresTopLevelDelimiter(): void
    {
        $boundary = new ArrowExpressionBoundary();

        self::assertTrue($boundary->isTerminator(new PhpToken(59, ';', 1, 0), 0, 0, 0));
        self::assertFalse($boundary->isTerminator(new PhpToken(59, ';', 1, 0), 1, 0, 0));
        self::assertFalse($boundary->isTerminator(new PhpToken(262, 'value', 1, 0), 0, 0, 0));
    }
}
