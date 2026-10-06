<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Php\Complexity;

use Guard\Collect\Php\ClassLikeMetric\ClassLikeDeclarationReader;
use Guard\Collect\Php\Complexity\CyclomaticComplexityCalculator;
use Guard\Collect\Php\Complexity\CyclomaticComplexityState;
use Guard\Collect\Php\Complexity\CyclomaticDecisionWeight;
use Guard\Collect\Php\FunctionMetric\ArrowExpressionBoundary;
use Guard\Collect\Php\FunctionMetric\ArrowFunctionMetricReader;
use Guard\Collect\Php\FunctionMetric\BlockFunctionMetricReader;
use Guard\Collect\Php\FunctionMetric\FunctionBodyLocator;
use Guard\Collect\Php\FunctionMetric\FunctionMetric;
use Guard\Collect\Php\FunctionMetric\FunctionMetricCollector;
use Guard\Collect\Php\FunctionMetric\FunctionMetricComplexityAssigner;
use Guard\Collect\Php\FunctionMetric\FunctionMetricLineCollector;
use Guard\Collect\Php\FunctionMetric\FunctionNameReader;
use Guard\Collect\Php\FunctionMetric\FunctionScanState;
use Guard\Collect\Php\FunctionMetric\NestedFunctionMetricRange;
use Guard\Collect\Php\Token\ClassLikeTokenMatcher;
use Guard\Collect\Php\Token\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Php\Complexity\CyclomaticComplexityCalculator
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric
 * @uses \Guard\Collect\Php\Complexity\CyclomaticComplexityState
 * @uses \Guard\Collect\Php\Complexity\CyclomaticDecisionWeight
 * @uses \Guard\Collect\Php\FunctionMetric\ArrowExpressionBoundary
 * @uses \Guard\Collect\Php\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Guard\Collect\Php\FunctionMetric\BlockFunctionMetricReader
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionBodyLocator
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetricCollector
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetricLineCollector
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionNameReader
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionScanState
 * @uses \Guard\Collect\Php\FunctionMetric\NestedFunctionMetricRange
 * @uses \Guard\Collect\Php\Token\ClassLikeTokenMatcher
 * @uses \Guard\Collect\Php\Token\PhpTokenNavigator
 */
#[CoversClass(CyclomaticComplexityCalculator::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(\Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric::class)]
#[UsesClass(CyclomaticComplexityState::class)]
#[UsesClass(CyclomaticDecisionWeight::class)]
#[UsesClass(ArrowExpressionBoundary::class)]
#[UsesClass(ArrowFunctionMetricReader::class)]
#[UsesClass(BlockFunctionMetricReader::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionMetricCollector::class)]
#[UsesClass(FunctionMetricComplexityAssigner::class)]
#[UsesClass(FunctionMetricLineCollector::class)]
#[UsesClass(FunctionNameReader::class)]
#[UsesClass(FunctionScanState::class)]
#[UsesClass(NestedFunctionMetricRange::class)]
#[UsesClass(ClassLikeTokenMatcher::class)]
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
