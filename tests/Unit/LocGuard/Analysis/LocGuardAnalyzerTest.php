<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\AnalysisResult;
use Toolkit\LocGuard\Analysis\ApplyRuleMatcher;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricCollector;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder;
use Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator;
use Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityState;
use Toolkit\LocGuard\Analysis\Complexity\CyclomaticDecisionWeight;
use Toolkit\LocGuard\Analysis\FileAnalysis;
use Toolkit\LocGuard\Analysis\FileMetric\FileMetric;
use Toolkit\LocGuard\Analysis\FileMetric\FileMetricViolationBuilder;
use Toolkit\LocGuard\Analysis\FilePolicyAssigner;
use Toolkit\LocGuard\Analysis\FilePolicyAssignment;
use Toolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader;
use Toolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionComplexityViolationBuilder;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionLineViolationBuilder;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricViolationBuilder;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionNameReader;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState;
use Toolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange;
use Toolkit\LocGuard\Analysis\LocGuardAnalyzer;
use Toolkit\LocGuard\Analysis\PhpFileAnalyzer;
use Toolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher;
use Toolkit\LocGuard\Analysis\Token\CodeTokenLineResolver;
use Toolkit\LocGuard\Analysis\Token\PhpTokenNavigator;
use Toolkit\LocGuard\Analysis\Token\TokenLineCounter;
use Toolkit\LocGuard\Analysis\Violation;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\LocGuardConfig;
use Toolkit\LocGuard\Config\Policy\ApplyConfig;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;
use Toolkit\LocGuard\Config\ReportConfig;
use Toolkit\LocGuard\Config\ScanConfig;
use Toolkit\LocGuard\Filesystem\FilePathPatternMatcher;
use Toolkit\LocGuard\Filesystem\LocGuardPathResolver;
use Toolkit\LocGuard\Filesystem\PhpFileFinder;
use Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy;
use Toolkit\LocGuard\Filesystem\PhpPathFileCollector;

/**
 * @covers \Toolkit\LocGuard\Analysis\LocGuardAnalyzer
 * @uses \Toolkit\LocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricCollector
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher
 * @uses \Toolkit\LocGuard\Analysis\Token\CodeTokenLineResolver
 * @uses \Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator
 * @uses \Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityState
 * @uses \Toolkit\LocGuard\Analysis\Complexity\CyclomaticDecisionWeight
 * @uses \Toolkit\LocGuard\Analysis\FileAnalysis
 * @uses \Toolkit\LocGuard\Analysis\ApplyRuleMatcher
 * @uses \Toolkit\LocGuard\Analysis\FilePolicyAssigner
 * @uses \Toolkit\LocGuard\Analysis\FilePolicyAssignment
 * @uses \Toolkit\LocGuard\Analysis\FileMetric\FileMetric
 * @uses \Toolkit\LocGuard\Analysis\FileMetric\FileMetricViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionComplexityViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionLineViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionNameReader
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleConfig
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfig
 * @uses \Toolkit\LocGuard\Config\ScanConfig
 * @uses \Toolkit\LocGuard\Filesystem\FilePathPatternMatcher
 * @uses \Toolkit\LocGuard\Filesystem\LocGuardPathResolver
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange
 * @uses \Toolkit\LocGuard\Analysis\PhpFileAnalyzer
 * @uses \Toolkit\LocGuard\Filesystem\PhpFileFinder
 * @uses \Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \Toolkit\LocGuard\Filesystem\PhpPathFileCollector
 * @uses \Toolkit\LocGuard\Analysis\Token\PhpTokenNavigator
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 * @uses \Toolkit\LocGuard\Analysis\Token\TokenLineCounter
 * @uses \Toolkit\LocGuard\Analysis\Violation
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
#[UsesClass(ApplyRuleMatcher::class)]
#[UsesClass(FilePolicyAssigner::class)]
#[UsesClass(FilePolicyAssignment::class)]
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
#[UsesClass(ApplyConfig::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(FilePathPatternMatcher::class)]
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

        $limits = new LimitConfig(100, 100, 100, 100, 100, 100, 3, 50, 20, 20);
        $result = (new LocGuardAnalyzer())->analyze(new LocGuardConfig(
            $dir,
            new ScanConfig(['src'], []),
            ['standard' => new PolicyConfig('standard', null, $limits)],
            new ApplyConfig('standard', []),
            new ReportConfig('ai', ['path', 'line', 'rule']),
        ));

        self::assertSame(1, $result->fileCount());
        self::assertSame('function_lines', $result->violations[0]->rule);
        self::assertSame('src/Example.php', $result->violations[0]->path);
    }

    public function testAnalyzesFilesWithTheirAssignedPolicyLimits(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-policy-analyzer-' . uniqid('', true);
        mkdir($dir . '/src', 0755, true);
        $source = <<<'PHP'
<?php

function long_function(): void
{
    echo 'one';
    echo 'two';
}
PHP;
        file_put_contents($dir . '/src/Example.php', $source);
        file_put_contents($dir . '/src/Native.php', $source);
        $standard = new PolicyConfig('standard', null, LimitConfig::fromValues(['function.lines' => 3]));
        $native = new PolicyConfig('native', 'standard', LimitConfig::fromValues(['function.lines' => 10]));
        $config = new LocGuardConfig(
            $dir,
            new ScanConfig(['src'], []),
            ['standard' => $standard, 'native' => $native],
            new ApplyConfig('standard', [new ApplyRuleConfig('native', ['src/Native.php'], 'native')]),
            new ReportConfig('ai', ['path']),
        );

        $result = (new LocGuardAnalyzer())->analyze($config);

        self::assertCount(1, $result->violations);
        self::assertSame('src/Example.php', $result->violations[0]->path);
        self::assertSame('standard', $result->violations[0]->policy);
    }
}
