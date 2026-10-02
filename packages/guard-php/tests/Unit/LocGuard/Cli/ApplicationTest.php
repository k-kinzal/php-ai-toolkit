<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

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
use Toolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary;
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
use Toolkit\LocGuard\Cli\Application;
use Toolkit\LocGuard\Cli\LocGuardAnalysisRunner;
use Toolkit\LocGuard\Cli\LocGuardCliArgumentParser;
use Toolkit\LocGuard\Cli\LocGuardCliValueOption;
use Toolkit\LocGuard\Cli\LocGuardCliValueOptionParser;
use Toolkit\LocGuard\Cli\LocGuardConfigPathResolver;
use Toolkit\LocGuard\Cli\LocGuardExplainRunner;
use Toolkit\LocGuard\Cli\LocGuardHelpText;
use Toolkit\LocGuard\Cli\LocGuardOutputWriter;
use Toolkit\LocGuard\Cli\LocGuardReporterOverride;
use Toolkit\LocGuard\Config\ConfigKeyValidator;
use Toolkit\LocGuard\Config\ConfigLoader;
use Toolkit\LocGuard\Config\ConfigScalarReader;
use Toolkit\LocGuard\Config\ConfigStringListReader;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\LimitConfigReader;
use Toolkit\LocGuard\Config\LocGuardConfig;
use Toolkit\LocGuard\Config\Policy\ApplyConfig;
use Toolkit\LocGuard\Config\Policy\ApplyConfigReader;
use Toolkit\LocGuard\Config\Policy\ApplyPolicyUsageValidator;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfigReader;
use Toolkit\LocGuard\Config\Policy\ApplyRuleListConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyDefinition;
use Toolkit\LocGuard\Config\Policy\PolicyListConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyResolver;
use Toolkit\LocGuard\Config\ReportConfig;
use Toolkit\LocGuard\Config\ReportConfigReader;
use Toolkit\LocGuard\Config\ScanConfig;
use Toolkit\LocGuard\Config\ScanConfigReader;
use Toolkit\LocGuard\Filesystem\LocGuardPathResolver;
use Toolkit\LocGuard\Filesystem\PhpFileFinder;
use Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy;
use Toolkit\LocGuard\Filesystem\PhpPathFileCollector;
use Toolkit\LocGuard\LocGuardException;
use Toolkit\LocGuard\Reporting\AiReporter;
use Toolkit\LocGuard\Reporting\AiReportGuidance;
use Toolkit\LocGuard\Reporting\AiReportSummary;
use Toolkit\LocGuard\Reporting\AiViolationAction;
use Toolkit\LocGuard\Reporting\AiViolationFormatter;
use Toolkit\LocGuard\Reporting\JsonReporter;
use Toolkit\LocGuard\Reporting\ReporterFactory;
use Toolkit\LocGuard\Reporting\TextReporter;
use Toolkit\LocGuard\Reporting\ViolationSorter;

/**
 * @covers \Toolkit\LocGuard\Cli\Application
 * @uses \Toolkit\LocGuard\Reporting\AiReportGuidance
 * @uses \Toolkit\LocGuard\Reporting\AiReportSummary
 * @uses \Toolkit\LocGuard\Reporting\AiReporter
 * @uses \Toolkit\LocGuard\Reporting\AiViolationAction
 * @uses \Toolkit\LocGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\LocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary
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
 * @uses \Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityState
 * @uses \Toolkit\LocGuard\Analysis\Complexity\CyclomaticDecisionWeight
 * @uses \Toolkit\LocGuard\Analysis\FileAnalysis
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
 * @uses \Toolkit\LocGuard\Reporting\JsonReporter
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LimitConfigReader
 * @uses \Toolkit\LocGuard\Cli\LocGuardAnalysisRunner
 * @uses \Toolkit\LocGuard\Analysis\LocGuardAnalyzer
 * @uses \Toolkit\LocGuard\Cli\LocGuardCliArgumentParser
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Cli\LocGuardConfigPathResolver
 * @uses \Toolkit\LocGuard\LocGuardException
 * @uses \Toolkit\LocGuard\Cli\LocGuardHelpText
 * @uses \Toolkit\LocGuard\Cli\LocGuardOutputWriter
 * @uses \Toolkit\LocGuard\Filesystem\LocGuardPathResolver
 * @uses \Toolkit\LocGuard\Cli\LocGuardReporterOverride
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange
 * @uses \Toolkit\LocGuard\Analysis\PhpFileAnalyzer
 * @uses \Toolkit\LocGuard\Filesystem\PhpFileFinder
 * @uses \Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \Toolkit\LocGuard\Filesystem\PhpPathFileCollector
 * @uses \Toolkit\LocGuard\Analysis\Token\PhpTokenNavigator
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 * @uses \Toolkit\LocGuard\Config\ReportConfigReader
 * @uses \Toolkit\LocGuard\Reporting\ReporterFactory
 * @uses \Toolkit\LocGuard\Reporting\TextReporter
 * @uses \Toolkit\LocGuard\Analysis\Token\TokenLineCounter
 * @uses \Toolkit\LocGuard\Analysis\Violation
 * @uses \Toolkit\LocGuard\Reporting\ViolationSorter
 * @uses \Toolkit\LocGuard\Analysis\ApplyRuleMatcher
 * @uses \Toolkit\LocGuard\Analysis\FilePolicyAssigner
 * @uses \Toolkit\LocGuard\Analysis\FilePolicyAssignment
 * @uses \Toolkit\LocGuard\Cli\LocGuardCliValueOption
 * @uses \Toolkit\LocGuard\Cli\LocGuardCliValueOptionParser
 * @uses \Toolkit\LocGuard\Cli\LocGuardExplainRunner
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyPolicyUsageValidator
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleListConfigReader
 * @uses \Toolkit\LocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyDefinition
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyListConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyResolver
 * @uses \Toolkit\LocGuard\Config\ScanConfig
 * @uses \Toolkit\LocGuard\Config\ScanConfigReader
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
#[UsesClass(ApplyRuleMatcher::class)]
#[UsesClass(FilePolicyAssigner::class)]
#[UsesClass(FilePolicyAssignment::class)]
#[UsesClass(LocGuardCliValueOption::class)]
#[UsesClass(LocGuardCliValueOptionParser::class)]
#[UsesClass(LocGuardExplainRunner::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(ApplyConfigReader::class)]
#[UsesClass(ApplyPolicyUsageValidator::class)]
#[UsesClass(ApplyRuleConfigReader::class)]
#[UsesClass(ApplyRuleListConfigReader::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(PolicyConfigReader::class)]
#[UsesClass(PolicyDefinition::class)]
#[UsesClass(PolicyListConfigReader::class)]
#[UsesClass(PolicyResolver::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(ScanConfigReader::class)]
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
scan:
  roots: [src]
policies:
  standard:
    limits:
      function: { lines: 3 }
apply:
  default: standard
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
scan:
  roots: [src]
policies:
  standard:
    limits:
      function: { lines: 3 }
apply:
  default: standard
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
scan:
  roots: [src]
policies:
  standard:
    limits:
      function: { lines: 50 }
apply:
  default: standard
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
scan:
  roots: [src]
policies:
  standard:
    limits:
      function: { lines: 50 }
apply:
  default: standard
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
scan:
  roots: [src]
policies:
  standard:
    limits:
      function: { lines: 50 }
apply:
  default: standard
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
