<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis\Complexity;

use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader;
use Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator;
use Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityState;
use Toolkit\LocGuard\Analysis\Complexity\CyclomaticDecisionWeight;
use Toolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary;
use Toolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader;
use Toolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionNameReader;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState;
use Toolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange;
use Toolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher;
use Toolkit\LocGuard\Analysis\Token\PhpTokenNavigator;

/**
 * @covers \Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Toolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher
 * @uses \Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityState
 * @uses \Toolkit\LocGuard\Analysis\Complexity\CyclomaticDecisionWeight
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionNameReader
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange
 * @uses \Toolkit\LocGuard\Analysis\Token\PhpTokenNavigator
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
