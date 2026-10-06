<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php\FunctionMetric;

use Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary;
use Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader;
use Guard\Structure\Php\FunctionMetric\FunctionBodyLocator;
use Guard\Structure\Php\FunctionMetric\FunctionMetric;
use Guard\Structure\Php\Token\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionBodyLocator
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Structure\Php\Token\PhpTokenNavigator
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
