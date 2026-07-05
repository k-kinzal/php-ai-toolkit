<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\ArrowExpressionBoundary;
use PhpAiToolkit\LocGuard\Analysis\ArrowFunctionMetricReader;
use PhpAiToolkit\LocGuard\Analysis\BlockFunctionMetricReader;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeDeclarationReader;
use PhpAiToolkit\LocGuard\Analysis\CyclomaticComplexityCalculator;
use PhpAiToolkit\LocGuard\Analysis\CyclomaticComplexityState;
use PhpAiToolkit\LocGuard\Analysis\CyclomaticDecisionWeight;
use PhpAiToolkit\LocGuard\Analysis\FunctionBodyLocator;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetricCollector;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetricComplexityAssigner;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetricLineCollector;
use PhpAiToolkit\LocGuard\Analysis\FunctionNameReader;
use PhpAiToolkit\LocGuard\Analysis\FunctionScanState;
use PhpAiToolkit\LocGuard\Analysis\NestedFunctionMetricRange;
use PhpAiToolkit\LocGuard\Analysis\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FunctionMetricCollector::class)]
#[UsesClass(ArrowExpressionBoundary::class)]
#[UsesClass(ArrowFunctionMetricReader::class)]
#[UsesClass(BlockFunctionMetricReader::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(CyclomaticComplexityCalculator::class)]
#[UsesClass(CyclomaticComplexityState::class)]
#[UsesClass(CyclomaticDecisionWeight::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionMetricComplexityAssigner::class)]
#[UsesClass(FunctionMetricLineCollector::class)]
#[UsesClass(FunctionNameReader::class)]
#[UsesClass(FunctionScanState::class)]
#[UsesClass(NestedFunctionMetricRange::class)]
#[UsesClass(PhpTokenNavigator::class)]
final class FunctionMetricCollectorTest extends TestCase
{
    public function testCollectReturnsFunctionMethodAndComplexityMetrics(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

function run(int $value): void
{
    if ($value > 0) {
        echo 'yes';
    }
}

final class Example
{
    public function handle(): void
    {
    }
}
PHP, TOKEN_PARSE));

        $metrics = (new FunctionMetricCollector())->collect($tokens);

        self::assertSame(['function', 'method'], array_map(static fn ($metric): string => $metric->kind, $metrics));
        self::assertSame(['run', 'Example::handle'], array_map(static fn ($metric): string => $metric->name, $metrics));
        self::assertSame(2, $metrics[0]->cyclomaticComplexity);
    }

    public function testCollectHandlesClosuresMultilineSignaturesAndIgnoresBodylessMethods(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

$closure = function (
    int $value
): int {
    return $value;
};

interface Contract
{
    public function run(): void;
}
PHP, TOKEN_PARSE));

        $metrics = (new FunctionMetricCollector())->collect($tokens);

        self::assertSame(['function'], array_map(static fn ($metric): string => $metric->kind, $metrics));
        self::assertSame(['{closure}'], array_map(static fn ($metric): string => $metric->name, $metrics));
        self::assertSame([5], array_map(static fn ($metric): int => $metric->lineCount(), $metrics));
    }

    public function testCollectDoesNotAddNestedClosureComplexityToOuterFunction(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

function outer(): void
{
    if (true) {
        echo 'outer';
    }
    $inner = function (): void {
        if (true) {
            echo 'inner';
        }
    };
}
PHP, TOKEN_PARSE));

        $metrics = (new FunctionMetricCollector())->collect($tokens);

        self::assertSame(['outer', '{closure}'], array_map(static fn ($metric): string => $metric->name, $metrics));
        self::assertSame([2, 2], array_map(static fn ($metric): int => $metric->cyclomaticComplexity, $metrics));
    }

    public function testCollectHandlesArrowFunctionsAsFunctionMetrics(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

$first = fn (
    int $value
): int =>
    $value > 0 ? $value : 0;

$second = array_map(
    fn (int $value): int => match ($value) {
        1 => 1,
        default => 0,
    },
    [1]
);
PHP, TOKEN_PARSE));

        $metrics = (new FunctionMetricCollector())->collect($tokens);

        self::assertSame(['function', 'function'], array_map(static fn ($metric): string => $metric->kind, $metrics));
        self::assertSame(['{closure}', '{closure}'], array_map(static fn ($metric): string => $metric->name, $metrics));
        self::assertSame([4, 4], array_map(static fn ($metric): int => $metric->lineCount(), $metrics));
        self::assertSame([2, 3], array_map(static fn ($metric): int => $metric->cyclomaticComplexity, $metrics));
    }

    public function testCollectDoesNotAddNestedArrowFunctionComplexityToOuterMethod(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

final class Example
{
    public function run(): void
    {
        $mapper = fn (int $value): int => $value > 0 ? $value : 0;
    }
}
PHP, TOKEN_PARSE));

        $metrics = (new FunctionMetricCollector())->collect($tokens);

        self::assertSame(['method', 'function'], array_map(static fn ($metric): string => $metric->kind, $metrics));
        self::assertSame(['Example::run', '{closure}'], array_map(static fn ($metric): string => $metric->name, $metrics));
        self::assertSame([1, 2], array_map(static fn ($metric): int => $metric->cyclomaticComplexity, $metrics));
    }

    public function testCollectHandlesArrowFunctionsReturningDelimitedExpressions(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

$arrayFactory = fn (): array => [
    'nested' => ['value' => 1],
];

$objectFactory = fn (): object => new class {
};
PHP, TOKEN_PARSE));

        $metrics = (new FunctionMetricCollector())->collect($tokens);

        self::assertSame(['function', 'function'], array_map(static fn ($metric): string => $metric->kind, $metrics));
        self::assertSame([3, 2], array_map(static fn ($metric): int => $metric->lineCount(), $metrics));
        self::assertSame([1, 1], array_map(static fn ($metric): int => $metric->cyclomaticComplexity, $metrics));
    }

    public function testCollectCountsMatchTernaryCoalesceAndBooleanOperators(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

function complex(bool $left, bool $right, ?int $fallback): int
{
    $value = $left && $right ? ($fallback ?? 1) : 0;
    return match ($value) {
        1 => 1,
        default => 0,
    };
}
PHP, TOKEN_PARSE));

        $metrics = (new FunctionMetricCollector())->collect($tokens);

        self::assertSame(['complex'], array_map(static fn ($metric): string => $metric->name, $metrics));
        self::assertSame([6], array_map(static fn ($metric): int => $metric->cyclomaticComplexity, $metrics));
    }

    public function testCollectCountsOnlyTopLevelMatchArmsAsMatchComplexity(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

function map_value(int $value): array
{
    return match ($value) {
        1 => ['nested' => 1],
        default => ['nested' => 0],
    };
}
PHP, TOKEN_PARSE));

        $metrics = (new FunctionMetricCollector())->collect($tokens);

        self::assertSame(['map_value'], array_map(static fn ($metric): string => $metric->name, $metrics));
        self::assertSame([3], array_map(static fn ($metric): int => $metric->cyclomaticComplexity, $metrics));
    }
}
