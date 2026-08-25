<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Cli;

use PhpAiToolkit\TreeGuard\Analysis\AnalysisResult;
use PhpAiToolkit\TreeGuard\Analysis\CaseConventionMatcher;
use PhpAiToolkit\TreeGuard\Analysis\ChildCountInspector;
use PhpAiToolkit\TreeGuard\Analysis\DepthInspector;
use PhpAiToolkit\TreeGuard\Analysis\DirectoryPatternMatcher;
use PhpAiToolkit\TreeGuard\Analysis\DirectoryRuleInspector;
use PhpAiToolkit\TreeGuard\Analysis\DirNameInspector;
use PhpAiToolkit\TreeGuard\Analysis\EmptyDirectoryInspector;
use PhpAiToolkit\TreeGuard\Analysis\FileNameInspector;
use PhpAiToolkit\TreeGuard\Analysis\RequiredFileInspector;
use PhpAiToolkit\TreeGuard\Analysis\TotalFileCountInspector;
use PhpAiToolkit\TreeGuard\Analysis\TreeGuardAnalyzer;
use PhpAiToolkit\TreeGuard\Analysis\Violation;
use PhpAiToolkit\TreeGuard\Cli\Application;
use PhpAiToolkit\TreeGuard\Cli\TreeGuardAnalysisRunner;
use PhpAiToolkit\TreeGuard\Cli\TreeGuardCliArgumentParser;
use PhpAiToolkit\TreeGuard\Cli\TreeGuardConfigPathResolver;
use PhpAiToolkit\TreeGuard\Cli\TreeGuardHelpText;
use PhpAiToolkit\TreeGuard\Cli\TreeGuardOutputWriter;
use PhpAiToolkit\TreeGuard\Cli\TreeGuardReporterOverride;
use PhpAiToolkit\TreeGuard\Config\ConfigLoader;
use PhpAiToolkit\TreeGuard\Config\ConfigScalarReader;
use PhpAiToolkit\TreeGuard\Config\ConfigStringListReader;
use PhpAiToolkit\TreeGuard\Config\ReportConfig;
use PhpAiToolkit\TreeGuard\Config\ReportConfigReader;
use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Config\RuleConfigReader;
use PhpAiToolkit\TreeGuard\Config\RuleListConfigReader;
use PhpAiToolkit\TreeGuard\Config\TreeGuardConfig;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListingReader;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryTreeScanner;
use PhpAiToolkit\TreeGuard\Filesystem\PathInclusionPolicy;
use PhpAiToolkit\TreeGuard\Filesystem\TreeGuardPathResolver;
use PhpAiToolkit\TreeGuard\Reporting\AiReporter;
use PhpAiToolkit\TreeGuard\Reporting\AiReportGuidance;
use PhpAiToolkit\TreeGuard\Reporting\AiReportSummary;
use PhpAiToolkit\TreeGuard\Reporting\AiViolationAction;
use PhpAiToolkit\TreeGuard\Reporting\AiViolationFormatter;
use PhpAiToolkit\TreeGuard\Reporting\JsonReporter;
use PhpAiToolkit\TreeGuard\Reporting\ReporterFactory;
use PhpAiToolkit\TreeGuard\Reporting\TextReporter;
use PhpAiToolkit\TreeGuard\Reporting\ViolationSorter;
use PhpAiToolkit\TreeGuard\TreeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\TreeGuard\Cli\Application
 * @uses \PhpAiToolkit\TreeGuard\Reporting\AiReportGuidance
 * @uses \PhpAiToolkit\TreeGuard\Reporting\AiReportSummary
 * @uses \PhpAiToolkit\TreeGuard\Reporting\AiReporter
 * @uses \PhpAiToolkit\TreeGuard\Reporting\AiViolationAction
 * @uses \PhpAiToolkit\TreeGuard\Reporting\AiViolationFormatter
 * @uses \PhpAiToolkit\TreeGuard\Analysis\AnalysisResult
 * @uses \PhpAiToolkit\TreeGuard\Analysis\CaseConventionMatcher
 * @uses \PhpAiToolkit\TreeGuard\Analysis\ChildCountInspector
 * @uses \PhpAiToolkit\TreeGuard\Config\ConfigLoader
 * @uses \PhpAiToolkit\TreeGuard\Config\ConfigScalarReader
 * @uses \PhpAiToolkit\TreeGuard\Config\ConfigStringListReader
 * @uses \PhpAiToolkit\TreeGuard\Analysis\DepthInspector
 * @uses \PhpAiToolkit\TreeGuard\Analysis\DirNameInspector
 * @uses \PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \PhpAiToolkit\TreeGuard\Filesystem\DirectoryListingReader
 * @uses \PhpAiToolkit\TreeGuard\Analysis\DirectoryPatternMatcher
 * @uses \PhpAiToolkit\TreeGuard\Analysis\DirectoryRuleInspector
 * @uses \PhpAiToolkit\TreeGuard\Filesystem\DirectoryTreeScanner
 * @uses \PhpAiToolkit\TreeGuard\Analysis\EmptyDirectoryInspector
 * @uses \PhpAiToolkit\TreeGuard\Analysis\FileNameInspector
 * @uses \PhpAiToolkit\TreeGuard\Reporting\JsonReporter
 * @uses \PhpAiToolkit\TreeGuard\Filesystem\PathInclusionPolicy
 * @uses \PhpAiToolkit\TreeGuard\Config\ReportConfig
 * @uses \PhpAiToolkit\TreeGuard\Config\ReportConfigReader
 * @uses \PhpAiToolkit\TreeGuard\Reporting\ReporterFactory
 * @uses \PhpAiToolkit\TreeGuard\Analysis\RequiredFileInspector
 * @uses \PhpAiToolkit\TreeGuard\Config\RuleConfig
 * @uses \PhpAiToolkit\TreeGuard\Config\RuleConfigReader
 * @uses \PhpAiToolkit\TreeGuard\Config\RuleListConfigReader
 * @uses \PhpAiToolkit\TreeGuard\Reporting\TextReporter
 * @uses \PhpAiToolkit\TreeGuard\Analysis\TotalFileCountInspector
 * @uses \PhpAiToolkit\TreeGuard\Cli\TreeGuardAnalysisRunner
 * @uses \PhpAiToolkit\TreeGuard\Analysis\TreeGuardAnalyzer
 * @uses \PhpAiToolkit\TreeGuard\Cli\TreeGuardCliArgumentParser
 * @uses \PhpAiToolkit\TreeGuard\Config\TreeGuardConfig
 * @uses \PhpAiToolkit\TreeGuard\Cli\TreeGuardConfigPathResolver
 * @uses \PhpAiToolkit\TreeGuard\TreeGuardException
 * @uses \PhpAiToolkit\TreeGuard\Cli\TreeGuardHelpText
 * @uses \PhpAiToolkit\TreeGuard\Cli\TreeGuardOutputWriter
 * @uses \PhpAiToolkit\TreeGuard\Filesystem\TreeGuardPathResolver
 * @uses \PhpAiToolkit\TreeGuard\Cli\TreeGuardReporterOverride
 * @uses \PhpAiToolkit\TreeGuard\Analysis\Violation
 * @uses \PhpAiToolkit\TreeGuard\Reporting\ViolationSorter
 */
#[CoversClass(Application::class)]
#[UsesClass(AiReportGuidance::class)]
#[UsesClass(AiReportSummary::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(AiViolationAction::class)]
#[UsesClass(AiViolationFormatter::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(CaseConventionMatcher::class)]
#[UsesClass(ChildCountInspector::class)]
#[UsesClass(ConfigLoader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(DepthInspector::class)]
#[UsesClass(DirNameInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(DirectoryListingReader::class)]
#[UsesClass(DirectoryPatternMatcher::class)]
#[UsesClass(DirectoryRuleInspector::class)]
#[UsesClass(DirectoryTreeScanner::class)]
#[UsesClass(EmptyDirectoryInspector::class)]
#[UsesClass(FileNameInspector::class)]
#[UsesClass(JsonReporter::class)]
#[UsesClass(PathInclusionPolicy::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ReportConfigReader::class)]
#[UsesClass(ReporterFactory::class)]
#[UsesClass(RequiredFileInspector::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(RuleConfigReader::class)]
#[UsesClass(RuleListConfigReader::class)]
#[UsesClass(TextReporter::class)]
#[UsesClass(TotalFileCountInspector::class)]
#[UsesClass(TreeGuardAnalysisRunner::class)]
#[UsesClass(TreeGuardAnalyzer::class)]
#[UsesClass(TreeGuardCliArgumentParser::class)]
#[UsesClass(TreeGuardConfig::class)]
#[UsesClass(TreeGuardConfigPathResolver::class)]
#[UsesClass(TreeGuardException::class)]
#[UsesClass(TreeGuardHelpText::class)]
#[UsesClass(TreeGuardOutputWriter::class)]
#[UsesClass(TreeGuardPathResolver::class)]
#[UsesClass(TreeGuardReporterOverride::class)]
#[UsesClass(Violation::class)]
#[UsesClass(ViolationSorter::class)]
final class ApplicationTest extends TestCase
{
    public function testRunReturnsZeroWhenNoViolationsExist(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-cli-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        touch($dir . '/src/Example.php');
        file_put_contents($dir . '/tree.yaml', <<<'YAML'
paths:
  - src
rules:
  - path: src
    allow: ['*.php']
    max_files: 5
YAML);

        $output = '';
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $app->run(['tree-guard']));
        self::assertStringContainsString('TREE_GUARD_PASSED', $output);
    }

    public function testRunReturnsOneWhenViolationsExist(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-cli-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        touch($dir . '/src/One.php');
        touch($dir . '/src/Two.php');
        file_put_contents($dir . '/tree.yaml', <<<'YAML'
paths:
  - src
rules:
  - path: src
    max_files: 1
YAML);

        $output = '';
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(1, $app->run(['tree-guard']));
        self::assertStringContainsString('[max_files]', $output);
    }

    public function testRunUsesReporterOverride(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-cli-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        touch($dir . '/src/Example.php');
        file_put_contents($dir . '/tree.yaml', <<<'YAML'
paths:
  - src
YAML);

        $output = '';
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $app->run(['tree-guard', '--reporter=json']));
        self::assertStringContainsString('"status": "passed"', $output);
    }

    public function testRunPrintsHelpAndVersion(): void
    {
        $output = '';
        $dir = sys_get_temp_dir() . '/treeguard-cli-' . uniqid('', true);
        mkdir($dir);
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $app->run(['tree-guard', '--help']));
        self::assertStringContainsString('Usage:', $output);

        $output = '';

        self::assertSame(0, $app->run(['tree-guard', '-V']));
        self::assertStringContainsString('tree-guard 1.0.0', $output);
    }

    public function testRunAcceptsAbsoluteConfigPathAndSeparateFormatOption(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-cli-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        touch($dir . '/src/Example.php');
        file_put_contents($dir . '/tree.yaml', <<<'YAML'
paths:
  - src
YAML);

        $output = '';
        $app = new Application($dir, stdout: static function (string $message) use (&$output): void {
            $output .= $message;
        });

        self::assertSame(0, $app->run(['tree-guard', '--config', $dir . '/tree.yaml', '--format', 'text']));
        self::assertStringContainsString('TreeGuard passed.', $output);
    }

    public function testRunReturnsTwoWhenConfigIsMissing(): void
    {
        $error = '';
        $dir = sys_get_temp_dir() . '/treeguard-cli-' . uniqid('', true);
        mkdir($dir);
        $app = new Application(
            $dir,
            stderr: static function (string $message) use (&$error): void {
                $error .= $message;
            },
        );

        self::assertSame(2, $app->run(['tree-guard']));
        self::assertStringContainsString('config not found', $error);
    }

    public function testRunRejectsUnknownOption(): void
    {
        $error = '';
        $dir = sys_get_temp_dir() . '/treeguard-cli-' . uniqid('', true);
        mkdir($dir);
        $app = new Application(
            $dir,
            stderr: static function (string $message) use (&$error): void {
                $error .= $message;
            },
        );

        self::assertSame(2, $app->run(['tree-guard', '--unknown']));
        self::assertStringContainsString('Unknown option: --unknown', $error);
    }
}
