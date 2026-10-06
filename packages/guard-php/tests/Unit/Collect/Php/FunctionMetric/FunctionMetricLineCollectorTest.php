<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Php\FunctionMetric;

use Guard\Collect\Php\ClassLikeMetric\ClassLikeDeclarationReader;
use Guard\Collect\Php\FunctionMetric\ArrowExpressionBoundary;
use Guard\Collect\Php\FunctionMetric\ArrowFunctionMetricReader;
use Guard\Collect\Php\FunctionMetric\BlockFunctionMetricReader;
use Guard\Collect\Php\FunctionMetric\FunctionBodyLocator;
use Guard\Collect\Php\FunctionMetric\FunctionMetric;
use Guard\Collect\Php\FunctionMetric\FunctionMetricLineCollector;
use Guard\Collect\Php\FunctionMetric\FunctionNameReader;
use Guard\Collect\Php\FunctionMetric\FunctionScanState;
use Guard\Collect\Php\Token\ClassLikeTokenMatcher;
use Guard\Collect\Php\Token\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Php\FunctionMetric\FunctionMetricLineCollector
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric
 * @uses \Guard\Collect\Php\FunctionMetric\ArrowExpressionBoundary
 * @uses \Guard\Collect\Php\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Guard\Collect\Php\FunctionMetric\BlockFunctionMetricReader
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionBodyLocator
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionNameReader
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionScanState
 * @uses \Guard\Collect\Php\Token\ClassLikeTokenMatcher
 * @uses \Guard\Collect\Php\Token\PhpTokenNavigator
 */
#[CoversClass(FunctionMetricLineCollector::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(\Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric::class)]
#[UsesClass(ArrowExpressionBoundary::class)]
#[UsesClass(ArrowFunctionMetricReader::class)]
#[UsesClass(BlockFunctionMetricReader::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionNameReader::class)]
#[UsesClass(FunctionScanState::class)]
#[UsesClass(ClassLikeTokenMatcher::class)]
#[UsesClass(PhpTokenNavigator::class)]
final class FunctionMetricLineCollectorTest extends TestCase
{
    public function testCollectReturnsFunctionMethodAndArrowFunctionLineMetrics(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

function run(): void
{
}

final class Example
{
    public function handle(): void
    {
    }
}

$mapper = fn (int $value): int => $value;
PHP, TOKEN_PARSE));

        $metrics = (new FunctionMetricLineCollector())->collect($tokens);

        self::assertSame(['function', 'method', 'function'], array_map(static fn ($metric): string => $metric->kind, $metrics));
        self::assertSame(['run', 'Example::handle', '{closure}'], array_map(static fn ($metric): string => $metric->name, $metrics));
    }
}
