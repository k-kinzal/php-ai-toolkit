<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php\FunctionMetric;

use Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary;
use Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader;
use Guard\Structure\Php\FunctionMetric\FunctionBodyLocator;
use Guard\Structure\Php\FunctionMetric\FunctionMetric;
use Guard\Structure\Php\FunctionMetric\FunctionNameReader;
use Guard\Structure\Php\FunctionMetric\FunctionScanState;
use Guard\Structure\Php\Token\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader
 * @uses \Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionBodyLocator
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionNameReader
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionScanState
 * @uses \Guard\Structure\Php\Token\PhpTokenNavigator
 */
#[CoversClass(BlockFunctionMetricReader::class)]
#[UsesClass(ArrowExpressionBoundary::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionNameReader::class)]
#[UsesClass(FunctionScanState::class)]
#[UsesClass(PhpTokenNavigator::class)]
final class BlockFunctionMetricReaderTest extends TestCase
{
    public function testMetricReturnsFunctionMetric(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php function run(): void {}', TOKEN_PARSE));

        $metric = (new BlockFunctionMetricReader())->metric($tokens, 1, new FunctionScanState());

        self::assertInstanceOf(FunctionMetric::class, $metric);
        self::assertSame('function', $metric->kind);
        self::assertSame('run', $metric->name);
        self::assertSame(10, $metric->bodyStartIndex);
        self::assertSame(11, $metric->bodyEndIndex);
    }

    public function testMetricReturnsMethodMetricInsideClass(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php final class Example { public function handle(): void {} }', TOKEN_PARSE));
        $state = new FunctionScanState();
        $state->registerClassBody(7, 'Example');
        $state->advance($tokens[7], 7);

        $metric = (new BlockFunctionMetricReader())->metric($tokens, 11, $state);

        self::assertInstanceOf(FunctionMetric::class, $metric);
        self::assertSame('method', $metric->kind);
        self::assertSame('Example::handle', $metric->name);
    }

    public function testMetricReturnsNullForBodylessMethod(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php interface Contract { public function run(): void; }', TOKEN_PARSE));

        self::assertNull((new BlockFunctionMetricReader())->metric($tokens, 9, new FunctionScanState()));
    }
}
