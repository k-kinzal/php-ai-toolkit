<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis\Complexity;

use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader;
use PhpAiToolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator;
use PhpAiToolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityState;
use PhpAiToolkit\LocGuard\Analysis\Complexity\CyclomaticDecisionWeight;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionNameReader;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange;
use PhpAiToolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher;
use PhpAiToolkit\LocGuard\Analysis\Token\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader
 * @uses \PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \PhpAiToolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher
 * @uses \PhpAiToolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityState
 * @uses \PhpAiToolkit\LocGuard\Analysis\Complexity\CyclomaticDecisionWeight
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionNameReader
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange
 * @uses \PhpAiToolkit\LocGuard\Analysis\Token\PhpTokenNavigator
 */
#[CoversClass(CyclomaticComplexityCalculator::class)]
#[UsesClass(ArrowExpressionBoundary::class)]
#[UsesClass(ArrowFunctionMetricReader::class)]
#[UsesClass(BlockFunctionMetricReader::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(ClassLikeTokenMatcher::class)]
#[UsesClass(CyclomaticComplexityState::class)]
#[UsesClass(CyclomaticDecisionWeight::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionMetricCollector::class)]
#[UsesClass(FunctionMetricComplexityAssigner::class)]
#[UsesClass(FunctionMetricLineCollector::class)]
#[UsesClass(FunctionNameReader::class)]
#[UsesClass(FunctionScanState::class)]
#[UsesClass(NestedFunctionMetricRange::class)]
#[UsesClass(PhpTokenNavigator::class)]
final class CyclomaticComplexityCalculatorTest extends TestCase
{
    public function testCalculateCountsBranchTokensAndTopLevelMatchArms(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

function map_value(int $value, bool $enabled): array
{
    if ($enabled && $value > 0) {
        return match ($value) {
            1 => ['nested' => 1],
            default => ['nested' => 0],
        };
    }

    return $value > 0 ? ['fallback' => $value] : [];
}
PHP, TOKEN_PARSE));
        $metrics = (new FunctionMetricCollector())->collect($tokens);

        self::assertSame(6, (new CyclomaticComplexityCalculator())->calculate($tokens, $metrics[0], $metrics));
    }

    public function testCalculateExcludesNestedFunctionMetrics(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

function outer(): void
{
    if (true) {
        echo 'outer';
    }
    $inner = fn (int $value): int => $value > 0 ? $value : 0;
}
PHP, TOKEN_PARSE));
        $metrics = (new FunctionMetricCollector())->collect($tokens);

        self::assertSame(2, (new CyclomaticComplexityCalculator())->calculate($tokens, $metrics[0], $metrics));
        self::assertSame(2, (new CyclomaticComplexityCalculator())->calculate($tokens, $metrics[1], $metrics));
    }
}
