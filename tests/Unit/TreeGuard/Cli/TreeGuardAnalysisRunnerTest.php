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
use PhpAiToolkit\TreeGuard\Cli\TreeGuardAnalysisRunner;
use PhpAiToolkit\TreeGuard\Cli\TreeGuardConfigPathResolver;
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
use PhpAiToolkit\TreeGuard\TreeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TreeGuardAnalysisRunner::class)]
#[UsesClass(AiReporter::class)]
#[UsesClass(AiReportGuidance::class)]
#[UsesClass(AiReportSummary::class)]
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
#[UsesClass(TotalFileCountInspector::class)]
#[UsesClass(TreeGuardAnalyzer::class)]
#[UsesClass(TreeGuardConfig::class)]
#[UsesClass(TreeGuardConfigPathResolver::class)]
#[UsesClass(TreeGuardException::class)]
#[UsesClass(TreeGuardOutputWriter::class)]
#[UsesClass(TreeGuardPathResolver::class)]
#[UsesClass(TreeGuardReporterOverride::class)]
#[UsesClass(Violation::class)]
final class TreeGuardAnalysisRunnerTest extends TestCase
{
    public function testRunReturnsZeroAndWritesReportWithoutViolations(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-runner-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        touch($dir . '/src/App.php');
        file_put_contents($dir . '/tree.yaml', "paths:\n  - src\n");
        $output = '';
        $writer = new TreeGuardOutputWriter(static function (string $message) use (&$output): void {
            $output .= $message;
        });
        $runner = new TreeGuardAnalysisRunner($dir, new ConfigLoader(), new TreeGuardAnalyzer(), new ReporterFactory(), $writer);

        self::assertSame(0, $runner->run('tree.yaml', null));
        self::assertStringContainsString('TREE_GUARD_PASSED', $output);
    }

    public function testRunReturnsOneAndAppliesReporterOverride(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-runner-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        touch($dir . '/src/notes.txt');
        file_put_contents($dir . '/tree.yaml', "paths:\n  - src\nrules:\n  - path: src\n    allow: ['*.php']\n");
        $output = '';
        $writer = new TreeGuardOutputWriter(static function (string $message) use (&$output): void {
            $output .= $message;
        });
        $runner = new TreeGuardAnalysisRunner($dir, new ConfigLoader(), new TreeGuardAnalyzer(), new ReporterFactory(), $writer);

        self::assertSame(1, $runner->run('tree.yaml', 'json'));
        self::assertStringContainsString('"status": "failed"', $output);
        self::assertStringContainsString('"rule": "disallowed_file"', $output);
    }

    public function testRunReturnsTwoAndWritesErrorForInvalidConfig(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-runner-' . uniqid('', true);
        mkdir($dir);
        $error = '';
        $writer = new TreeGuardOutputWriter(null, static function (string $message) use (&$error): void {
            $error .= $message;
        });
        $runner = new TreeGuardAnalysisRunner($dir, new ConfigLoader(), new TreeGuardAnalyzer(), new ReporterFactory(), $writer);

        self::assertSame(2, $runner->run('tree.yaml', null));
        self::assertStringContainsString('TreeGuard error: TreeGuard config not found:', $error);
    }
}
