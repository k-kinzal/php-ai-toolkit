<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\ArrowExpressionBoundary;
use PhpAiToolkit\LocGuard\Analysis\BlockFunctionMetricReader;
use PhpAiToolkit\LocGuard\Analysis\FunctionBodyLocator;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric;
use PhpAiToolkit\LocGuard\Analysis\FunctionNameReader;
use PhpAiToolkit\LocGuard\Analysis\FunctionScanState;
use PhpAiToolkit\LocGuard\Analysis\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
