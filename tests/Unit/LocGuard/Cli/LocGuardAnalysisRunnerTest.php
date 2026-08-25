<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PhpAiToolkit\LocGuard\Analysis\AnalysisResult;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricCollector;
use PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator;
use PhpAiToolkit\LocGuard\Analysis\FileAnalysis;
use PhpAiToolkit\LocGuard\Analysis\FileMetric\FileMetric;
use PhpAiToolkit\LocGuard\Analysis\FileMetric\FileMetricViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricViolationBuilder;
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState;
use PhpAiToolkit\LocGuard\Analysis\LocGuardAnalyzer;
use PhpAiToolkit\LocGuard\Analysis\PhpFileAnalyzer;
use PhpAiToolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher;
use PhpAiToolkit\LocGuard\Analysis\Token\CodeTokenLineResolver;
use PhpAiToolkit\LocGuard\Analysis\Token\TokenLineCounter;
use PhpAiToolkit\LocGuard\Cli\LocGuardAnalysisRunner;
use PhpAiToolkit\LocGuard\Cli\LocGuardConfigPathResolver;
use PhpAiToolkit\LocGuard\Cli\LocGuardOutputWriter;
use PhpAiToolkit\LocGuard\Cli\LocGuardReporterOverride;
use PhpAiToolkit\LocGuard\Config\ConfigLoader;
use PhpAiToolkit\LocGuard\Config\ConfigScalarReader;
use PhpAiToolkit\LocGuard\Config\ConfigStringListReader;
use PhpAiToolkit\LocGuard\Config\LimitConfig;
use PhpAiToolkit\LocGuard\Config\LimitConfigReader;
use PhpAiToolkit\LocGuard\Config\LocGuardConfig;
use PhpAiToolkit\LocGuard\Config\ReportConfig;
use PhpAiToolkit\LocGuard\Config\ReportConfigReader;
use PhpAiToolkit\LocGuard\Filesystem\LocGuardPathResolver;
use PhpAiToolkit\LocGuard\Filesystem\PhpFileFinder;
use PhpAiToolkit\LocGuard\Filesystem\PhpFileInclusionPolicy;
use PhpAiToolkit\LocGuard\Filesystem\PhpPathFileCollector;
use PhpAiToolkit\LocGuard\Reporting\AiReporter;
use PhpAiToolkit\LocGuard\Reporting\AiReportSummary;
use PhpAiToolkit\LocGuard\Reporting\AiViolationFormatter;
use PhpAiToolkit\LocGuard\Reporting\ReporterFactory;
use PhpAiToolkit\LocGuard\Reporting\ViolationSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\LocGuard\Cli\LocGuardAnalysisRunner
 * @uses \PhpAiToolkit\LocGuard\Reporting\AiReportSummary
 * @uses \PhpAiToolkit\LocGuard\Reporting\AiReporter
 * @uses \PhpAiToolkit\LocGuard\Reporting\AiViolationFormatter
 * @uses \PhpAiToolkit\LocGuard\Analysis\AnalysisResult
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader
 * @uses \PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricCollector
 * @uses \PhpAiToolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder
 * @uses \PhpAiToolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher
 * @uses \PhpAiToolkit\LocGuard\Analysis\Token\CodeTokenLineResolver
 * @uses \PhpAiToolkit\LocGuard\Config\ConfigLoader
 * @uses \PhpAiToolkit\LocGuard\Config\ConfigScalarReader
 * @uses \PhpAiToolkit\LocGuard\Config\ConfigStringListReader
 * @uses \PhpAiToolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator
 * @uses \PhpAiToolkit\LocGuard\Analysis\FileAnalysis
 * @uses \PhpAiToolkit\LocGuard\Analysis\FileMetric\FileMetric
 * @uses \PhpAiToolkit\LocGuard\Analysis\FileMetric\FileMetricViolationBuilder
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricViolationBuilder
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState
 * @uses \PhpAiToolkit\LocGuard\Config\LimitConfig
 * @uses \PhpAiToolkit\LocGuard\Config\LimitConfigReader
 * @uses \PhpAiToolkit\LocGuard\Analysis\LocGuardAnalyzer
 * @uses \PhpAiToolkit\LocGuard\Config\LocGuardConfig
 * @uses \PhpAiToolkit\LocGuard\Cli\LocGuardConfigPathResolver
 * @uses \PhpAiToolkit\LocGuard\Cli\LocGuardOutputWriter
 * @uses \PhpAiToolkit\LocGuard\Filesystem\LocGuardPathResolver
 * @uses \PhpAiToolkit\LocGuard\Cli\LocGuardReporterOverride
 * @uses \PhpAiToolkit\LocGuard\Analysis\PhpFileAnalyzer
 * @uses \PhpAiToolkit\LocGuard\Filesystem\PhpFileFinder
 * @uses \PhpAiToolkit\LocGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \PhpAiToolkit\LocGuard\Filesystem\PhpPathFileCollector
 * @uses \PhpAiToolkit\LocGuard\Config\ReportConfig
 * @uses \PhpAiToolkit\LocGuard\Config\ReportConfigReader
 * @uses \PhpAiToolkit\LocGuard\Reporting\ReporterFactory
 * @uses \PhpAiToolkit\LocGuard\Analysis\Token\TokenLineCounter
 * @uses \PhpAiToolkit\LocGuard\Reporting\ViolationSorter
 */
#[CoversClass(LocGuardAnalysisRunner::class)]
#[UsesClass(AiReportSummary::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(AiViolationFormatter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(ArrowFunctionMetricReader::class)]
#[UsesClass(BlockFunctionMetricReader::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(ClassLikeMetricCollector::class)]
#[UsesClass(ClassLikeMetricViolationBuilder::class)]
#[UsesClass(ClassLikeTokenMatcher::class)]
#[UsesClass(CodeTokenLineResolver::class)]
#[UsesClass(ConfigLoader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(CyclomaticComplexityCalculator::class)]
#[UsesClass(FileAnalysis::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(FileMetricViolationBuilder::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionMetricCollector::class)]
#[UsesClass(FunctionMetricComplexityAssigner::class)]
#[UsesClass(FunctionMetricLineCollector::class)]
#[UsesClass(FunctionMetricViolationBuilder::class)]
#[UsesClass(FunctionScanState::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(LocGuardAnalyzer::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(LocGuardConfigPathResolver::class)]
#[UsesClass(LocGuardOutputWriter::class)]
#[UsesClass(LocGuardPathResolver::class)]
#[UsesClass(LocGuardReporterOverride::class)]
#[UsesClass(PhpFileAnalyzer::class)]
#[UsesClass(PhpFileFinder::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(PhpPathFileCollector::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ReportConfigReader::class)]
#[UsesClass(ReporterFactory::class)]
#[UsesClass(TokenLineCounter::class)]
#[UsesClass(ViolationSorter::class)]
final class LocGuardAnalysisRunnerTest extends TestCase
{
    public function testRunWritesReportAndReturnsAnalyzerExitCode(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-runner-' . uniqid('', true);
        mkdir($dir . '/src', 0755, true);
        file_put_contents($dir . '/src/Example.php', "<?php\n");
        file_put_contents($dir . '/loc.yaml', "paths:\n  - src\n");
        $output = '';

        $exitCode = (new LocGuardAnalysisRunner(
            $dir,
            new ConfigLoader(),
            new LocGuardAnalyzer(),
            new ReporterFactory(),
            new LocGuardOutputWriter(stdout: static function (string $message) use (&$output): void {
                $output .= $message;
            }),
        ))->run('loc.yaml', null);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('LOC_GUARD_PASSED', $output);
    }
}
