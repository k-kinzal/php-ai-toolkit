<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

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
use Toolkit\TreeGuard\Config\ReportConfig;
use Toolkit\TreeGuard\Config\RuleConfig;
use Toolkit\TreeGuard\Config\TreeGuardConfig;
use Toolkit\TreeGuard\Filesystem\DirectoryListing;
use Toolkit\TreeGuard\Filesystem\DirectoryListingReader;
use Toolkit\TreeGuard\Filesystem\DirectoryTreeScanner;
use Toolkit\TreeGuard\Filesystem\PathInclusionPolicy;
use Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver;

/**
 * @covers \Toolkit\TreeGuard\Analysis\TreeGuardAnalyzer
 * @uses \Toolkit\TreeGuard\Analysis\AnalysisResult
 * @uses \Toolkit\TreeGuard\Analysis\CaseConventionMatcher
 * @uses \Toolkit\TreeGuard\Analysis\ChildCountInspector
 * @uses \Toolkit\TreeGuard\Analysis\DepthInspector
 * @uses \Toolkit\TreeGuard\Analysis\DirNameInspector
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListingReader
 * @uses \Toolkit\TreeGuard\Analysis\DirectoryPatternMatcher
 * @uses \Toolkit\TreeGuard\Analysis\DirectoryRuleInspector
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryTreeScanner
 * @uses \Toolkit\TreeGuard\Analysis\EmptyDirectoryInspector
 * @uses \Toolkit\TreeGuard\Analysis\FileNameInspector
 * @uses \Toolkit\TreeGuard\Filesystem\PathInclusionPolicy
 * @uses \Toolkit\TreeGuard\Config\ReportConfig
 * @uses \Toolkit\TreeGuard\Analysis\RequiredFileInspector
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 * @uses \Toolkit\TreeGuard\Analysis\TotalFileCountInspector
 * @uses \Toolkit\TreeGuard\Config\TreeGuardConfig
 * @uses \Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver
 * @uses \Toolkit\TreeGuard\Analysis\Violation
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
