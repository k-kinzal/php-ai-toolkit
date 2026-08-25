<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Analysis\AnalysisResult;
use Toolkit\TreeGuard\Analysis\CaseConventionMatcher;
use Toolkit\TreeGuard\Analysis\ChildCountInspector;
use Toolkit\TreeGuard\Analysis\DepthInspector;
use Toolkit\TreeGuard\Analysis\DirectoryPatternMatcher;
use Toolkit\TreeGuard\Analysis\DirectoryRuleInspector;
use Toolkit\TreeGuard\Analysis\DirNameInspector;
use Toolkit\TreeGuard\Analysis\EmptyDirectoryInspector;
use Toolkit\TreeGuard\Analysis\FileNameInspector;
use Toolkit\TreeGuard\Analysis\RequiredFileInspector;
use Toolkit\TreeGuard\Analysis\TotalFileCountInspector;
use Toolkit\TreeGuard\Analysis\TreeGuardAnalyzer;
use Toolkit\TreeGuard\Analysis\Violation;
use Toolkit\TreeGuard\Cli\Application;
use Toolkit\TreeGuard\Cli\TreeGuardAnalysisRunner;
use Toolkit\TreeGuard\Cli\TreeGuardCliArgumentParser;
use Toolkit\TreeGuard\Cli\TreeGuardConfigPathResolver;
use Toolkit\TreeGuard\Cli\TreeGuardHelpText;
use Toolkit\TreeGuard\Cli\TreeGuardOutputWriter;
use Toolkit\TreeGuard\Cli\TreeGuardReporterOverride;
use Toolkit\TreeGuard\Config\ConfigLoader;
use Toolkit\TreeGuard\Config\ConfigScalarReader;
use Toolkit\TreeGuard\Config\ConfigStringListReader;
use Toolkit\TreeGuard\Config\ReportConfig;
use Toolkit\TreeGuard\Config\ReportConfigReader;
use Toolkit\TreeGuard\Config\RuleConfig;
use Toolkit\TreeGuard\Config\RuleConfigReader;
use Toolkit\TreeGuard\Config\RuleListConfigReader;
use Toolkit\TreeGuard\Config\TreeGuardConfig;
use Toolkit\TreeGuard\Filesystem\DirectoryListing;
use Toolkit\TreeGuard\Filesystem\DirectoryListingReader;
use Toolkit\TreeGuard\Filesystem\DirectoryTreeScanner;
use Toolkit\TreeGuard\Filesystem\PathInclusionPolicy;
use Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver;
use Toolkit\TreeGuard\Reporting\AiReporter;
use Toolkit\TreeGuard\Reporting\AiReportGuidance;
use Toolkit\TreeGuard\Reporting\AiReportSummary;
use Toolkit\TreeGuard\Reporting\AiViolationAction;
use Toolkit\TreeGuard\Reporting\AiViolationFormatter;
use Toolkit\TreeGuard\Reporting\JsonReporter;
use Toolkit\TreeGuard\Reporting\ReporterFactory;
use Toolkit\TreeGuard\Reporting\TextReporter;
use Toolkit\TreeGuard\Reporting\ViolationSorter;
use Toolkit\TreeGuard\TreeGuardException;

/**
 * @covers \Toolkit\TreeGuard\Cli\Application
 * @uses \Toolkit\TreeGuard\Reporting\AiReportGuidance
 * @uses \Toolkit\TreeGuard\Reporting\AiReportSummary
 * @uses \Toolkit\TreeGuard\Reporting\AiReporter
 * @uses \Toolkit\TreeGuard\Reporting\AiViolationAction
 * @uses \Toolkit\TreeGuard\Reporting\AiViolationFormatter
 * @uses \Toolkit\TreeGuard\Analysis\AnalysisResult
 * @uses \Toolkit\TreeGuard\Analysis\CaseConventionMatcher
 * @uses \Toolkit\TreeGuard\Analysis\ChildCountInspector
 * @uses \Toolkit\TreeGuard\Config\ConfigLoader
 * @uses \Toolkit\TreeGuard\Config\ConfigScalarReader
 * @uses \Toolkit\TreeGuard\Config\ConfigStringListReader
 * @uses \Toolkit\TreeGuard\Analysis\DepthInspector
 * @uses \Toolkit\TreeGuard\Analysis\DirNameInspector
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListingReader
 * @uses \Toolkit\TreeGuard\Analysis\DirectoryPatternMatcher
 * @uses \Toolkit\TreeGuard\Analysis\DirectoryRuleInspector
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryTreeScanner
 * @uses \Toolkit\TreeGuard\Analysis\EmptyDirectoryInspector
 * @uses \Toolkit\TreeGuard\Analysis\FileNameInspector
 * @uses \Toolkit\TreeGuard\Reporting\JsonReporter
 * @uses \Toolkit\TreeGuard\Filesystem\PathInclusionPolicy
 * @uses \Toolkit\TreeGuard\Config\ReportConfig
 * @uses \Toolkit\TreeGuard\Config\ReportConfigReader
 * @uses \Toolkit\TreeGuard\Reporting\ReporterFactory
 * @uses \Toolkit\TreeGuard\Analysis\RequiredFileInspector
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 * @uses \Toolkit\TreeGuard\Config\RuleConfigReader
 * @uses \Toolkit\TreeGuard\Config\RuleListConfigReader
 * @uses \Toolkit\TreeGuard\Reporting\TextReporter
 * @uses \Toolkit\TreeGuard\Analysis\TotalFileCountInspector
 * @uses \Toolkit\TreeGuard\Cli\TreeGuardAnalysisRunner
 * @uses \Toolkit\TreeGuard\Analysis\TreeGuardAnalyzer
 * @uses \Toolkit\TreeGuard\Cli\TreeGuardCliArgumentParser
 * @uses \Toolkit\TreeGuard\Config\TreeGuardConfig
 * @uses \Toolkit\TreeGuard\Cli\TreeGuardConfigPathResolver
 * @uses \Toolkit\TreeGuard\TreeGuardException
 * @uses \Toolkit\TreeGuard\Cli\TreeGuardHelpText
 * @uses \Toolkit\TreeGuard\Cli\TreeGuardOutputWriter
 * @uses \Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver
 * @uses \Toolkit\TreeGuard\Cli\TreeGuardReporterOverride
 * @uses \Toolkit\TreeGuard\Analysis\Violation
 * @uses \Toolkit\TreeGuard\Reporting\ViolationSorter
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
