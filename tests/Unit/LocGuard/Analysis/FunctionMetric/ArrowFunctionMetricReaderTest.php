<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis\FunctionMetric;

use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric;
use PhpAiToolkit\LocGuard\Analysis\Token\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric
 * @uses \PhpAiToolkit\LocGuard\Analysis\Token\PhpTokenNavigator
 */
#[CoversClass(ArrowFunctionMetricReader::class)]
#[UsesClass(ArrowExpressionBoundary::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(PhpTokenNavigator::class)]
final class ArrowFunctionMetricReaderTest extends TestCase
{
    public function testMetricReturnsArrowFunctionMetric(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php $value = fn (int $n): int => $n;', TOKEN_PARSE));

        $metric = (new ArrowFunctionMetricReader())->metric($tokens, 5);

        self::assertInstanceOf(FunctionMetric::class, $metric);
        self::assertSame('function', $metric->kind);
        self::assertSame('{closure}', $metric->name);
        self::assertSame(16, $metric->bodyStartIndex);
        self::assertSame(19, $metric->bodyEndIndex);
    }

    public function testMetricReturnsNullWhenArrowBodyIsMissing(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php $value = fn (int $n): int'));

        self::assertNull((new ArrowFunctionMetricReader())->metric($tokens, 5));
    }
}
