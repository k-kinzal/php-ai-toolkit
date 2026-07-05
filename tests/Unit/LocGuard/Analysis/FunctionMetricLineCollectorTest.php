<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\ArrowExpressionBoundary;
use PhpAiToolkit\LocGuard\Analysis\ArrowFunctionMetricReader;
use PhpAiToolkit\LocGuard\Analysis\BlockFunctionMetricReader;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeDeclarationReader;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeTokenMatcher;
use PhpAiToolkit\LocGuard\Analysis\FunctionBodyLocator;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetricLineCollector;
use PhpAiToolkit\LocGuard\Analysis\FunctionNameReader;
use PhpAiToolkit\LocGuard\Analysis\FunctionScanState;
use PhpAiToolkit\LocGuard\Analysis\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FunctionMetricLineCollector::class)]
#[UsesClass(ArrowExpressionBoundary::class)]
#[UsesClass(ArrowFunctionMetricReader::class)]
#[UsesClass(BlockFunctionMetricReader::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(ClassLikeTokenMatcher::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionNameReader::class)]
#[UsesClass(FunctionScanState::class)]
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
