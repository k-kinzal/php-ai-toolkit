<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

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
use PhpAiToolkit\TreeGuard\Config\ReportConfig;
use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Config\TreeGuardConfig;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListingReader;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryTreeScanner;
use PhpAiToolkit\TreeGuard\Filesystem\PathInclusionPolicy;
use PhpAiToolkit\TreeGuard\Filesystem\TreeGuardPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\TreeGuard\Analysis\TreeGuardAnalyzer
 * @uses \PhpAiToolkit\TreeGuard\Analysis\AnalysisResult
 * @uses \PhpAiToolkit\TreeGuard\Analysis\CaseConventionMatcher
 * @uses \PhpAiToolkit\TreeGuard\Analysis\ChildCountInspector
 * @uses \PhpAiToolkit\TreeGuard\Analysis\DepthInspector
 * @uses \PhpAiToolkit\TreeGuard\Analysis\DirNameInspector
 * @uses \PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \PhpAiToolkit\TreeGuard\Filesystem\DirectoryListingReader
 * @uses \PhpAiToolkit\TreeGuard\Analysis\DirectoryPatternMatcher
 * @uses \PhpAiToolkit\TreeGuard\Analysis\DirectoryRuleInspector
 * @uses \PhpAiToolkit\TreeGuard\Filesystem\DirectoryTreeScanner
 * @uses \PhpAiToolkit\TreeGuard\Analysis\EmptyDirectoryInspector
 * @uses \PhpAiToolkit\TreeGuard\Analysis\FileNameInspector
 * @uses \PhpAiToolkit\TreeGuard\Filesystem\PathInclusionPolicy
 * @uses \PhpAiToolkit\TreeGuard\Config\ReportConfig
 * @uses \PhpAiToolkit\TreeGuard\Analysis\RequiredFileInspector
 * @uses \PhpAiToolkit\TreeGuard\Config\RuleConfig
 * @uses \PhpAiToolkit\TreeGuard\Analysis\TotalFileCountInspector
 * @uses \PhpAiToolkit\TreeGuard\Config\TreeGuardConfig
 * @uses \PhpAiToolkit\TreeGuard\Filesystem\TreeGuardPathResolver
 * @uses \PhpAiToolkit\TreeGuard\Analysis\Violation
 */
#[CoversClass(TreeGuardAnalyzer::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(CaseConventionMatcher::class)]
#[UsesClass(ChildCountInspector::class)]
#[UsesClass(DepthInspector::class)]
#[UsesClass(DirNameInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(DirectoryListingReader::class)]
#[UsesClass(DirectoryPatternMatcher::class)]
#[UsesClass(DirectoryRuleInspector::class)]
#[UsesClass(DirectoryTreeScanner::class)]
#[UsesClass(EmptyDirectoryInspector::class)]
#[UsesClass(FileNameInspector::class)]
#[UsesClass(PathInclusionPolicy::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(RequiredFileInspector::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(TotalFileCountInspector::class)]
#[UsesClass(TreeGuardConfig::class)]
#[UsesClass(TreeGuardPathResolver::class)]
#[UsesClass(Violation::class)]
final class TreeGuardAnalyzerTest extends TestCase
{
    public function testAnalyzeReportsViolationsForMatchedDirectories(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-analyze-' . uniqid('', true);
        mkdir($dir . '/src/A', 0777, true);
        touch($dir . '/src/A/One.php');
        touch($dir . '/src/A/Two.php');
        $rule = new RuleConfig('src/*', 1, null, null, null, null, null, null, null, null, false, null, null);
        $config = new TreeGuardConfig($dir, ['src'], [], [$rule], new ReportConfig('ai', ['path', 'rule']));

        $result = (new TreeGuardAnalyzer())->analyze($config);

        self::assertTrue($result->hasViolations());
        self::assertSame(1, $result->violationCount());
        self::assertSame('src/A', $result->violations[0]->path);
        self::assertSame('max_files', $result->violations[0]->rule);
        self::assertSame(2, $result->directoryCount());
        self::assertSame(2, $result->fileCount());
    }

    public function testAnalyzeReturnsCleanResultWhenNoRuleMatches(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-analyze-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        touch($dir . '/src/One.php');
        $rule = new RuleConfig('tests/*', 1, null, null, null, null, null, null, null, null, false, null, null);
        $config = new TreeGuardConfig($dir, ['src'], [], [$rule], new ReportConfig('ai', ['path', 'rule']));

        $result = (new TreeGuardAnalyzer())->analyze($config);

        self::assertFalse($result->hasViolations());
        self::assertSame(1, $result->directoryCount());
    }

    public function testAnalyzeAppliesOverlappingRulesIndependently(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-analyze-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        touch($dir . '/src/one.php');
        $first = new RuleConfig('src', null, null, null, null, ['*.txt'], null, null, null, null, false, null, null);
        $second = new RuleConfig('src/**', null, null, null, null, null, null, null, null, null, false, 'pascal', null);
        $config = new TreeGuardConfig($dir, ['src'], [], [$first, $second], new ReportConfig('ai', ['path', 'rule']));

        $result = (new TreeGuardAnalyzer())->analyze($config);

        self::assertSame(2, $result->violationCount());
        self::assertSame('disallowed_file', $result->violations[0]->rule);
        self::assertSame('src', $result->violations[0]->pattern);
        self::assertSame('file_case', $result->violations[1]->rule);
        self::assertSame('src/**', $result->violations[1]->pattern);
    }
}
