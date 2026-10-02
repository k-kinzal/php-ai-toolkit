<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis\FunctionMetric;

use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary;
use Toolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionNameReader;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState;
use Toolkit\LocGuard\Analysis\Token\PhpTokenNavigator;

/**
 * @covers \Toolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionNameReader
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState
 * @uses \Toolkit\LocGuard\Analysis\Token\PhpTokenNavigator
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
