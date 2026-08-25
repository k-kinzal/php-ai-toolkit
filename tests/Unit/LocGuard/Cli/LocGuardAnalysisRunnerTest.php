<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\AnalysisResult;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricCollector;
use Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder;
use Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator;
use Toolkit\LocGuard\Analysis\FileAnalysis;
use Toolkit\LocGuard\Analysis\FileMetric\FileMetric;
use Toolkit\LocGuard\Analysis\FileMetric\FileMetricViolationBuilder;
use Toolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader;
use Toolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricViolationBuilder;
use Toolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState;
use Toolkit\LocGuard\Analysis\LocGuardAnalyzer;
use Toolkit\LocGuard\Analysis\PhpFileAnalyzer;
use Toolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher;
use Toolkit\LocGuard\Analysis\Token\CodeTokenLineResolver;
use Toolkit\LocGuard\Analysis\Token\TokenLineCounter;
use Toolkit\LocGuard\Cli\LocGuardAnalysisRunner;
use Toolkit\LocGuard\Cli\LocGuardConfigPathResolver;
use Toolkit\LocGuard\Cli\LocGuardOutputWriter;
use Toolkit\LocGuard\Cli\LocGuardReporterOverride;
use Toolkit\LocGuard\Config\ConfigLoader;
use Toolkit\LocGuard\Config\ConfigScalarReader;
use Toolkit\LocGuard\Config\ConfigStringListReader;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\LimitConfigReader;
use Toolkit\LocGuard\Config\LocGuardConfig;
use Toolkit\LocGuard\Config\ReportConfig;
use Toolkit\LocGuard\Config\ReportConfigReader;
use Toolkit\LocGuard\Filesystem\LocGuardPathResolver;
use Toolkit\LocGuard\Filesystem\PhpFileFinder;
use Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy;
use Toolkit\LocGuard\Filesystem\PhpPathFileCollector;
use Toolkit\LocGuard\Reporting\AiReporter;
use Toolkit\LocGuard\Reporting\AiReportSummary;
use Toolkit\LocGuard\Reporting\AiViolationFormatter;
use Toolkit\LocGuard\Reporting\ReporterFactory;
use Toolkit\LocGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\LocGuard\Cli\LocGuardAnalysisRunner
 * @uses \Toolkit\LocGuard\Reporting\AiReportSummary
 * @uses \Toolkit\LocGuard\Reporting\AiReporter
 * @uses \Toolkit\LocGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\LocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricCollector
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher
 * @uses \Toolkit\LocGuard\Analysis\Token\CodeTokenLineResolver
 * @uses \Toolkit\LocGuard\Config\ConfigLoader
 * @uses \Toolkit\LocGuard\Config\ConfigScalarReader
 * @uses \Toolkit\LocGuard\Config\ConfigStringListReader
 * @uses \Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator
 * @uses \Toolkit\LocGuard\Analysis\FileAnalysis
 * @uses \Toolkit\LocGuard\Analysis\FileMetric\FileMetric
 * @uses \Toolkit\LocGuard\Analysis\FileMetric\FileMetricViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LimitConfigReader
 * @uses \Toolkit\LocGuard\Analysis\LocGuardAnalyzer
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Cli\LocGuardConfigPathResolver
 * @uses \Toolkit\LocGuard\Cli\LocGuardOutputWriter
 * @uses \Toolkit\LocGuard\Filesystem\LocGuardPathResolver
 * @uses \Toolkit\LocGuard\Cli\LocGuardReporterOverride
 * @uses \Toolkit\LocGuard\Analysis\PhpFileAnalyzer
 * @uses \Toolkit\LocGuard\Filesystem\PhpFileFinder
 * @uses \Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \Toolkit\LocGuard\Filesystem\PhpPathFileCollector
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 * @uses \Toolkit\LocGuard\Config\ReportConfigReader
 * @uses \Toolkit\LocGuard\Reporting\ReporterFactory
 * @uses \Toolkit\LocGuard\Analysis\Token\TokenLineCounter
 * @uses \Toolkit\LocGuard\Reporting\ViolationSorter
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
