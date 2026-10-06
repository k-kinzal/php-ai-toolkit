<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php\FunctionMetric;

use Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader;
use Guard\Structure\Php\Complexity\CyclomaticComplexityCalculator;
use Guard\Structure\Php\Complexity\CyclomaticComplexityState;
use Guard\Structure\Php\Complexity\CyclomaticDecisionWeight;
use Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader;
use Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader;
use Guard\Structure\Php\FunctionMetric\FunctionBodyLocator;
use Guard\Structure\Php\FunctionMetric\FunctionLineParser;
use Guard\Structure\Php\FunctionMetric\FunctionMetric;
use Guard\Structure\Php\FunctionMetric\FunctionMetricComplexityAssigner;
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
 * @covers \Guard\Structure\Php\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Guard\Structure\Php\Complexity\CyclomaticComplexityCalculator
 * @uses \Guard\Structure\Php\Complexity\CyclomaticComplexityState
 * @uses \Guard\Structure\Php\Complexity\CyclomaticDecisionWeight
 * @uses \Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary
 * @uses \Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionBodyLocator
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionLineParser
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionNameReader
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionScanState
 * @uses \Guard\Structure\Php\FunctionMetric\NestedFunctionMetricRange
 * @uses \Guard\Structure\Php\Token\ClassLikeTokenMatcher
 * @uses \Guard\Structure\Php\Token\PhpTokenNavigator
 */
#[CoversClass(FunctionMetricComplexityAssigner::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(CyclomaticComplexityCalculator::class)]
#[UsesClass(CyclomaticComplexityState::class)]
#[UsesClass(CyclomaticDecisionWeight::class)]
#[UsesClass(\Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary::class)]
#[UsesClass(ArrowFunctionMetricReader::class)]
#[UsesClass(BlockFunctionMetricReader::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionLineParser::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionNameReader::class)]
#[UsesClass(FunctionScanState::class)]
#[UsesClass(NestedFunctionMetricRange::class)]
#[UsesClass(ClassLikeTokenMatcher::class)]
#[UsesClass(PhpTokenNavigator::class)]
final class FunctionMetricComplexityAssignerTest extends TestCase
{
    public function testAssignFillsCyclomaticComplexityOnMetrics(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

function run(bool $enabled): void
{
    if ($enabled) {
    }
}
PHP, TOKEN_PARSE));
        $metrics = (new FunctionLineParser())->collect($tokens);

        (new FunctionMetricComplexityAssigner())->assign($tokens, $metrics);

        self::assertSame(2, $metrics[0]->cyclomaticComplexity);
    }
}
