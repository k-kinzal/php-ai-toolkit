<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php\Complexity;

use Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader;
use Guard\Structure\Php\Complexity\CyclomaticComplexityCalculator;
use Guard\Structure\Php\Complexity\CyclomaticComplexityState;
use Guard\Structure\Php\Complexity\CyclomaticDecisionWeight;
use Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary;
use Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader;
use Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader;
use Guard\Structure\Php\FunctionMetric\FunctionBodyLocator;
use Guard\Structure\Php\FunctionMetric\FunctionLineParser;
use Guard\Structure\Php\FunctionMetric\FunctionMetric;
use Guard\Structure\Php\FunctionMetric\FunctionMetricComplexityAssigner;
use Guard\Structure\Php\FunctionMetric\FunctionMetricParser;
use Guard\Structure\Php\FunctionMetric\FunctionNameReader;
use Guard\Structure\Php\FunctionMetric\FunctionScanState;
use Guard\Structure\Php\FunctionMetric\NestedFunctionMetricRange;
use Guard\Structure\Php\Token\ClassLikeTokenMatcher;
use Guard\Structure\Php\Token\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Php\Complexity\CyclomaticComplexityCalculator
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Guard\Structure\Php\Complexity\CyclomaticComplexityState
 * @uses \Guard\Structure\Php\Complexity\CyclomaticDecisionWeight
 * @uses \Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary
 * @uses \Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionBodyLocator
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionLineParser
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetricParser
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionNameReader
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionScanState
 * @uses \Guard\Structure\Php\FunctionMetric\NestedFunctionMetricRange
 * @uses \Guard\Structure\Php\Token\ClassLikeTokenMatcher
 * @uses \Guard\Structure\Php\Token\PhpTokenNavigator
 */
#[CoversClass(CyclomaticComplexityCalculator::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(CyclomaticComplexityState::class)]
#[UsesClass(CyclomaticDecisionWeight::class)]
#[UsesClass(ArrowExpressionBoundary::class)]
#[UsesClass(ArrowFunctionMetricReader::class)]
#[UsesClass(BlockFunctionMetricReader::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionLineParser::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionMetricComplexityAssigner::class)]
#[UsesClass(FunctionMetricParser::class)]
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
        $metrics = (new FunctionMetricParser())->collect($tokens);

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
        $metrics = (new FunctionMetricParser())->collect($tokens);

        self::assertSame(2, (new CyclomaticComplexityCalculator())->calculate($tokens, $metrics[0], $metrics));
        self::assertSame(2, (new CyclomaticComplexityCalculator())->calculate($tokens, $metrics[1], $metrics));
    }
}
