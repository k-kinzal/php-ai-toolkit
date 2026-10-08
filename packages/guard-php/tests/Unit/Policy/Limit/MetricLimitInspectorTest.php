<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Limit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Limit\MetricLimitInspector
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Policy\Definition\LimitConfig
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Policy\Limit\ClassLikeMetricLimit
 * @uses \Guard\Policy\Limit\ClassLikeMetricViolationBuilder
 * @uses \Guard\Policy\Limit\FileMetricViolationBuilder
 * @uses \Guard\Policy\Limit\FunctionComplexityViolationBuilder
 * @uses \Guard\Policy\Limit\FunctionLineViolationBuilder
 * @uses \Guard\Policy\Limit\FunctionMetricViolationBuilder
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Policy\Diagnostic\MetricViolation
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeMetricParser
 * @uses \Guard\Structure\Php\Complexity\CyclomaticComplexityCalculator
 * @uses \Guard\Structure\Php\Complexity\CyclomaticComplexityState
 * @uses \Guard\Structure\Php\Complexity\CyclomaticDecisionWeight
 * @uses \Guard\Structure\Php\FileMetric\FileMetric
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
 * @uses \Guard\Structure\Php\MetricParser
 * @uses \Guard\Structure\Php\SourceMetrics
 * @uses \Guard\Structure\Php\TokenParser
 * @uses \Guard\Structure\Php\Token\ClassLikeTokenMatcher
 * @uses \Guard\Structure\Php\Token\CodeTokenLineResolver
 * @uses \Guard\Structure\Php\Token\PhpTokenNavigator
 * @uses \Guard\Structure\Php\Token\TokenLineCounter
 * @uses \Guard\Structure\Php\Tokens
 * @uses \Guard\Structure\Source
 */
#[CoversClass(\Guard\Policy\Limit\MetricLimitInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\LimitConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\ClassLikeMetricLimit::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\ClassLikeMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FileMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FunctionComplexityViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FunctionLineViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FunctionMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\Finding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Diagnostic\MetricViolation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\ClassLikeMetric\ClassLikeMetricParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Complexity\CyclomaticComplexityCalculator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Complexity\CyclomaticComplexityState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Complexity\CyclomaticDecisionWeight::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FileMetric\FileMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionBodyLocator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionLineParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionMetricComplexityAssigner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionMetricParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionNameReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionScanState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\NestedFunctionMetricRange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\MetricParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\SourceMetrics::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\TokenParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Token\ClassLikeTokenMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Token\CodeTokenLineResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Token\PhpTokenNavigator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Token\TokenLineCounter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Tokens::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Source::class)]
final class MetricLimitInspectorTest extends TestCase
{
    public function testViolationsKeepsInclusiveThresholdsAndUnreadableSourceSemantics(): void
    {
        $metrics = (new \Guard\Structure\Php\MetricParser())->parse("<?php\necho 1;\n", 'memory.php');
        $inspector = new \Guard\Policy\Limit\MetricLimitInspector();
        self::assertSame([], $inspector->violations($metrics, \Guard\Policy\Definition\LimitConfig::fromValues(['file.lines' => 2, 'file.ncloc' => 1])));
        self::assertCount(1, $inspector->violations($metrics, \Guard\Policy\Definition\LimitConfig::fromValues(['file.lines' => 1])));
    }
}
