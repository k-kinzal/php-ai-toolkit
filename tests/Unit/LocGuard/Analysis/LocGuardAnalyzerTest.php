<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\AnalysisResult;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricCollector;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator;
use PhpAiToolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityState;
use PhpAiToolkit\LocGuard\Analysis\Complexity\CyclomaticDecisionWeight;
use PhpAiToolkit\LocGuard\Analysis\FileAnalysis;
use PhpAiToolkit\LocGuard\Analysis\FileMetric\FileMetric;
use PhpAiToolkit\LocGuard\Analysis\FileMetric\FileMetricViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionComplexityViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionLineViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionNameReader;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange;
use PhpAiToolkit\LocGuard\Analysis\LocGuardAnalyzer;
use PhpAiToolkit\LocGuard\Analysis\PhpFileAnalyzer;
use PhpAiToolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher;
use PhpAiToolkit\LocGuard\Analysis\Token\CodeTokenLineResolver;
use PhpAiToolkit\LocGuard\Analysis\Token\PhpTokenNavigator;
use PhpAiToolkit\LocGuard\Analysis\Token\TokenLineCounter;
use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PhpAiToolkit\LocGuard\Config\LocGuardConfig;
use PhpAiToolkit\LocGuard\Config\ReportConfig;
use PhpAiToolkit\LocGuard\Filesystem\LocGuardPathResolver;
use PhpAiToolkit\LocGuard\Filesystem\PhpFileFinder;
use PhpAiToolkit\LocGuard\Filesystem\PhpFileInclusionPolicy;
use PhpAiToolkit\LocGuard\Filesystem\PhpPathFileCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\LocGuard\Analysis\LocGuardAnalyzer
 * @uses \PhpAiToolkit\LocGuard\Analysis\AnalysisResult
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader
 * @uses \PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricCollector
 * @uses \PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder
 * @uses \PhpAiToolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher
 * @uses \PhpAiToolkit\LocGuard\Analysis\Token\CodeTokenLineResolver
 * @uses \PhpAiToolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator
 * @uses \PhpAiToolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityState
 * @uses \PhpAiToolkit\LocGuard\Analysis\Complexity\CyclomaticDecisionWeight
 * @uses \PhpAiToolkit\LocGuard\Analysis\FileAnalysis
 * @uses \PhpAiToolkit\LocGuard\Analysis\FileMetric\FileMetric
 * @uses \PhpAiToolkit\LocGuard\Analysis\FileMetric\FileMetricViolationBuilder
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionComplexityViolationBuilder
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionLineViolationBuilder
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricViolationBuilder
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionNameReader
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState
 * @uses \PhpAiToolkit\LocGuard\Config\LimitConfig
 * @uses \PhpAiToolkit\LocGuard\Config\LocGuardConfig
 * @uses \PhpAiToolkit\LocGuard\Filesystem\LocGuardPathResolver
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange
 * @uses \PhpAiToolkit\LocGuard\Analysis\PhpFileAnalyzer
 * @uses \PhpAiToolkit\LocGuard\Filesystem\PhpFileFinder
 * @uses \PhpAiToolkit\LocGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \PhpAiToolkit\LocGuard\Filesystem\PhpPathFileCollector
 * @uses \PhpAiToolkit\LocGuard\Analysis\Token\PhpTokenNavigator
 * @uses \PhpAiToolkit\LocGuard\Config\ReportConfig
 * @uses \PhpAiToolkit\LocGuard\Analysis\Token\TokenLineCounter
 * @uses \PhpAiToolkit\LocGuard\Analysis\Violation
 */
#[CoversClass(LocGuardAnalyzer::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(ArrowFunctionMetricReader::class)]
#[UsesClass(BlockFunctionMetricReader::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(ClassLikeMetricCollector::class)]
#[UsesClass(ClassLikeMetricViolationBuilder::class)]
#[UsesClass(ClassLikeTokenMatcher::class)]
#[UsesClass(CodeTokenLineResolver::class)]
#[UsesClass(CyclomaticComplexityCalculator::class)]
#[UsesClass(CyclomaticComplexityState::class)]
#[UsesClass(CyclomaticDecisionWeight::class)]
#[UsesClass(FileAnalysis::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(FileMetricViolationBuilder::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionComplexityViolationBuilder::class)]
#[UsesClass(FunctionLineViolationBuilder::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionMetricCollector::class)]
#[UsesClass(FunctionMetricComplexityAssigner::class)]
#[UsesClass(FunctionMetricLineCollector::class)]
#[UsesClass(FunctionMetricViolationBuilder::class)]
#[UsesClass(FunctionNameReader::class)]
#[UsesClass(FunctionScanState::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(LocGuardPathResolver::class)]
#[UsesClass(NestedFunctionMetricRange::class)]
#[UsesClass(PhpFileAnalyzer::class)]
#[UsesClass(PhpFileFinder::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(PhpPathFileCollector::class)]
#[UsesClass(PhpTokenNavigator::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(TokenLineCounter::class)]
#[UsesClass(Violation::class)]
final class LocGuardAnalyzerTest extends TestCase
{
    public function testAnalyzesConfiguredSourceFiles(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-analyzer-' . uniqid('', true);
        mkdir($dir);
        mkdir($dir . '/src');
        file_put_contents($dir . '/src/Example.php', <<<'PHP'
<?php

function too_long(): void
{
    echo '1';
    echo '2';
}
PHP);

        $result = (new LocGuardAnalyzer())->analyze(new LocGuardConfig(
            $dir,
            ['src'],
            [],
            new LimitConfig(100, 100, 100, 100, 100, 100, 3, 50, 20),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        ));

        self::assertSame(1, $result->fileCount());
        self::assertSame('function_lines', $result->violations[0]->rule);
        self::assertSame('src/Example.php', $result->violations[0]->path);
    }
}
