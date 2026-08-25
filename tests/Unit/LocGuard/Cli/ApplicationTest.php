<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

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
use PhpAiToolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary;
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
use PhpAiToolkit\LocGuard\Cli\Application;
use PhpAiToolkit\LocGuard\Cli\LocGuardAnalysisRunner;
use PhpAiToolkit\LocGuard\Cli\LocGuardCliArgumentParser;
use PhpAiToolkit\LocGuard\Cli\LocGuardConfigPathResolver;
use PhpAiToolkit\LocGuard\Cli\LocGuardHelpText;
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
use PhpAiToolkit\LocGuard\LocGuardException;
use PhpAiToolkit\LocGuard\Reporting\AiReporter;
use PhpAiToolkit\LocGuard\Reporting\AiReportGuidance;
use PhpAiToolkit\LocGuard\Reporting\AiReportSummary;
use PhpAiToolkit\LocGuard\Reporting\AiViolationAction;
use PhpAiToolkit\LocGuard\Reporting\AiViolationFormatter;
use PhpAiToolkit\LocGuard\Reporting\JsonReporter;
use PhpAiToolkit\LocGuard\Reporting\ReporterFactory;
use PhpAiToolkit\LocGuard\Reporting\TextReporter;
use PhpAiToolkit\LocGuard\Reporting\ViolationSorter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\LocGuard\Cli\Application
 * @uses \PhpAiToolkit\LocGuard\Reporting\AiReportGuidance
 * @uses \PhpAiToolkit\LocGuard\Reporting\AiReportSummary
 * @uses \PhpAiToolkit\LocGuard\Reporting\AiReporter
 * @uses \PhpAiToolkit\LocGuard\Reporting\AiViolationAction
 * @uses \PhpAiToolkit\LocGuard\Reporting\AiViolationFormatter
 * @uses \PhpAiToolkit\LocGuard\Analysis\AnalysisResult
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary
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
 * @uses \PhpAiToolkit\LocGuard\Reporting\JsonReporter
 * @uses \PhpAiToolkit\LocGuard\Config\LimitConfig
 * @uses \PhpAiToolkit\LocGuard\Config\LimitConfigReader
 * @uses \PhpAiToolkit\LocGuard\Cli\LocGuardAnalysisRunner
 * @uses \PhpAiToolkit\LocGuard\Analysis\LocGuardAnalyzer
 * @uses \PhpAiToolkit\LocGuard\Cli\LocGuardCliArgumentParser
 * @uses \PhpAiToolkit\LocGuard\Config\LocGuardConfig
 * @uses \PhpAiToolkit\LocGuard\Cli\LocGuardConfigPathResolver
 * @uses \PhpAiToolkit\LocGuard\LocGuardException
 * @uses \PhpAiToolkit\LocGuard\Cli\LocGuardHelpText
 * @uses \PhpAiToolkit\LocGuard\Cli\LocGuardOutputWriter
 * @uses \PhpAiToolkit\LocGuard\Filesystem\LocGuardPathResolver
 * @uses \PhpAiToolkit\LocGuard\Cli\LocGuardReporterOverride
 * @uses \PhpAiToolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange
 * @uses \PhpAiToolkit\LocGuard\Analysis\PhpFileAnalyzer
 * @uses \PhpAiToolkit\LocGuard\Filesystem\PhpFileFinder
 * @uses \PhpAiToolkit\LocGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \PhpAiToolkit\LocGuard\Filesystem\PhpPathFileCollector
 * @uses \PhpAiToolkit\LocGuard\Analysis\Token\PhpTokenNavigator
 * @uses \PhpAiToolkit\LocGuard\Config\ReportConfig
 * @uses \PhpAiToolkit\LocGuard\Config\ReportConfigReader
 * @uses \PhpAiToolkit\LocGuard\Reporting\ReporterFactory
 * @uses \PhpAiToolkit\LocGuard\Reporting\TextReporter
 * @uses \PhpAiToolkit\LocGuard\Analysis\Token\TokenLineCounter
 * @uses \PhpAiToolkit\LocGuard\Analysis\Violation
 * @uses \PhpAiToolkit\LocGuard\Reporting\ViolationSorter
 */
#[CoversClass(Application::class)]
#[UsesClass(AiReportGuidance::class)]
#[UsesClass(AiReportSummary::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(AiViolationFormatter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(ArrowExpressionBoundary::class)]
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
#[UsesClass(JsonReporter::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(LocGuardAnalysisRunner::class)]
#[UsesClass(LocGuardAnalyzer::class)]
#[UsesClass(LocGuardCliArgumentParser::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(LocGuardConfigPathResolver::class)]
#[UsesClass(LocGuardException::class)]
#[UsesClass(LocGuardHelpText::class)]
#[UsesClass(LocGuardOutputWriter::class)]
#[UsesClass(LocGuardPathResolver::class)]
#[UsesClass(LocGuardReporterOverride::class)]
#[UsesClass(NestedFunctionMetricRange::class)]
#[UsesClass(PhpFileAnalyzer::class)]
#[UsesClass(PhpFileFinder::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(PhpPathFileCollector::class)]
#[UsesClass(PhpTokenNavigator::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ReportConfigReader::class)]
#[UsesClass(ReporterFactory::class)]
#[UsesClass(TextReporter::class)]
#[UsesClass(TokenLineCounter::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationSorter::class)]
final class ApplicationTest extends TestCase
{
    public function testRunReturnsZeroWhenNoViolationsExist(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-cli-' . uniqid('', true);
        mkdir($dir);
        mkdir($dir . '/src');
        file_put_contents($dir . '/src/Example.php', <<<'PHP'
<?php

function small(): void
{
}
PHP);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
paths:
  - src
limits:
  max_file_lines: 100
  max_function_lines: 3
  max_method_lines: 3
  max_cyclomatic_complexity: 20
YAML);

        $output = '';
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $app->run(['loc-guard']));
        self::assertStringContainsString('LOC_GUARD_PASSED', $output);
    }

    public function testRunReturnsOneWhenViolationsExist(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-cli-' . uniqid('', true);
        mkdir($dir);
        mkdir($dir . '/src');
        file_put_contents($dir . '/src/Example.php', <<<'PHP'
<?php

function too_long(): void
{
    echo '1';
    echo '2';
    echo '3';
}
PHP);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
paths:
  - src
limits:
  max_file_lines: 100
  max_function_lines: 3
  max_method_lines: 3
  max_cyclomatic_complexity: 20
YAML);

        $output = '';
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(1, $app->run(['loc-guard']));
        self::assertStringContainsString('[function_lines]', $output);
    }

    public function testRunUsesReporterOverride(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-cli-' . uniqid('', true);
        mkdir($dir);
        mkdir($dir . '/src');
        file_put_contents($dir . '/src/Example.php', <<<'PHP'
<?php

function small(): void
{
}
PHP);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
paths:
  - src
YAML);

        $output = '';
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $app->run(['loc-guard', '--reporter=json']));
        self::assertStringContainsString('"status": "passed"', $output);
    }

    public function testRunPrintsHelpAndVersion(): void
    {
        $output = '';
        $dir = sys_get_temp_dir() . '/locguard-cli-' . uniqid('', true);
        mkdir($dir);
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $app->run(['loc-guard', '--help']));
        self::assertStringContainsString('Usage:', $output);

        $output = '';

        self::assertSame(0, $app->run(['loc-guard', '-V']));
        self::assertStringContainsString('loc-guard 1.0.0', $output);
    }

    public function testRunAcceptsAbsoluteConfigPathAndSeparateFormatOption(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-cli-' . uniqid('', true);
        mkdir($dir);
        mkdir($dir . '/src');
        file_put_contents($dir . '/src/Example.php', <<<'PHP'
<?php

function small(): void
{
}
PHP);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
paths:
  - src
YAML);

        $output = '';
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $app->run(['loc-guard', '--config', $dir . '/loc.yaml', '--format', 'text']));
        self::assertStringContainsString('LocGuard passed.', $output);
    }

    public function testRunAcceptsEqualsConfigAndFormatOptions(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-cli-' . uniqid('', true);
        mkdir($dir);
        mkdir($dir . '/src');
        file_put_contents($dir . '/src/Example.php', <<<'PHP'
<?php

function small(): void
{
}
PHP);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
paths:
  - src
YAML);

        $output = '';
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $app->run(['loc-guard', '--config=' . $dir . '/loc.yaml', '--format=json']));
        self::assertStringContainsString('"status": "passed"', $output);
    }

    public function testRunReturnsTwoWhenConfigIsMissing(): void
    {
        $error = '';
        $dir = sys_get_temp_dir() . '/locguard-cli-' . uniqid('', true);
        mkdir($dir);
        $app = new Application(
            $dir,
            stderr: static function (string $message) use (&$error): void {
                $error .= $message;
            },
        );

        self::assertSame(2, $app->run(['loc-guard']));
        self::assertStringContainsString('config not found', $error);
    }

    public function testRunRejectsUnknownOption(): void
    {
        $error = '';
        $dir = sys_get_temp_dir() . '/locguard-cli-' . uniqid('', true);
        mkdir($dir);
        $app = new Application(
            $dir,
            stderr: static function (string $message) use (&$error): void {
                $error .= $message;
            },
        );

        self::assertSame(2, $app->run(['loc-guard', '--unknown']));
        self::assertStringContainsString('Unknown option: --unknown', $error);
    }

    public function testRunRejectsMissingOptionValue(): void
    {
        $error = '';
        $dir = sys_get_temp_dir() . '/locguard-cli-' . uniqid('', true);
        mkdir($dir);
        $app = new Application(
            $dir,
            stderr: static function (string $message) use (&$error): void {
                $error .= $message;
            },
        );

        self::assertSame(2, $app->run(['loc-guard', '--config']));
        self::assertStringContainsString('Missing value for --config.', $error);
    }

    public function testRunRejectsMissingReporterValueBeforeNextOption(): void
    {
        $error = '';
        $dir = sys_get_temp_dir() . '/locguard-cli-' . uniqid('', true);
        mkdir($dir);
        $app = new Application(
            $dir,
            stderr: static function (string $message) use (&$error): void {
                $error .= $message;
            },
        );

        self::assertSame(2, $app->run(['loc-guard', '--reporter', '--config']));
        self::assertStringContainsString('Missing value for --reporter.', $error);
    }
}
