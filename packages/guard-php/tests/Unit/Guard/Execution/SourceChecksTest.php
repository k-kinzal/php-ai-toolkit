<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Execution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Execution\SourceChecks
 * @uses \Toolkit\DocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\DocGuard\Analysis\DocGuardAnalyzer
 * @uses \Toolkit\DocGuard\Analysis\HeadingHunkClassifier
 * @uses \Toolkit\DocGuard\Analysis\HeadingSequenceAligner
 * @uses \Toolkit\DocGuard\Analysis\HeadingStructureComparator
 * @uses \Toolkit\DocGuard\Analysis\UndeclaredDocumentInspector
 * @uses \Toolkit\DocGuard\Analysis\Violation
 * @uses \Toolkit\DocGuard\Analysis\ViolationFactory
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Config\DocGuardConfig
 * @uses \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Filesystem\DocGuardPathResolver
 * @uses \Toolkit\DocGuard\Filesystem\MarkdownFileFinder
 * @uses \Toolkit\DocGuard\Filesystem\MarkdownFileReader
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Markdown\BlockLineScanner
 * @uses \Toolkit\DocGuard\Markdown\BlockMarkerMatcher
 * @uses \Toolkit\DocGuard\Markdown\Fence
 * @uses \Toolkit\DocGuard\Markdown\FenceMatcher
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Markdown\HeadingParser
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 * @uses \Toolkit\DocGuard\Markdown\HtmlBlockMatcher
 * @uses \Toolkit\DocGuard\Markdown\LineIndentation
 * @uses \Toolkit\DocGuard\Markdown\LineScanner
 * @uses \Toolkit\DocGuard\Markdown\MarkdownLineSplitter
 * @uses \Toolkit\DocGuard\Markdown\ParserState
 * @uses \Toolkit\DocGuard\Markdown\SetextUnderlineMatcher
 * @uses \Toolkit\Guard\Config\Configuration
 * @uses \Toolkit\Guard\Config\MetricsReader
 * @uses \Toolkit\Guard\Config\Schema
 * @uses \Toolkit\Guard\Policy\PolicyException
 * @uses \Toolkit\Guard\Policy\Rule
 * @uses \Toolkit\Guard\Reporting\Finding
 * @uses \Toolkit\LocGuard\Analysis\AnalysisResult
 * @uses \Toolkit\LocGuard\Analysis\ApplyRuleMatcher
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetric
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricCollector
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricLimit
 * @uses \Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator
 * @uses \Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityState
 * @uses \Toolkit\LocGuard\Analysis\Complexity\CyclomaticDecisionWeight
 * @uses \Toolkit\LocGuard\Analysis\FileAnalysis
 * @uses \Toolkit\LocGuard\Analysis\FileMetric\FileMetric
 * @uses \Toolkit\LocGuard\Analysis\FileMetric\FileMetricViolationBuilder
 * @uses \Toolkit\LocGuard\Analysis\FilePolicyAssigner
 * @uses \Toolkit\LocGuard\Analysis\FilePolicyAssignment
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader
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
 * @uses \Toolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange
 * @uses \Toolkit\LocGuard\Analysis\LocGuardAnalyzer
 * @uses \Toolkit\LocGuard\Analysis\PhpFileAnalyzer
 * @uses \Toolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher
 * @uses \Toolkit\LocGuard\Analysis\Token\CodeTokenLineResolver
 * @uses \Toolkit\LocGuard\Analysis\Token\PhpTokenNavigator
 * @uses \Toolkit\LocGuard\Analysis\Token\TokenLineCounter
 * @uses \Toolkit\LocGuard\Analysis\Violation
 * @uses \Toolkit\LocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\LocGuard\Config\ConfigScalarReader
 * @uses \Toolkit\LocGuard\Config\ConfigStringListReader
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LimitConfigReader
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyPolicyUsageValidator
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleListConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyDefinition
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyListConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyResolver
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 * @uses \Toolkit\LocGuard\Config\ScanConfig
 * @uses \Toolkit\LocGuard\Filesystem\FilePathPatternMatcher
 * @uses \Toolkit\LocGuard\Filesystem\LocGuardPathResolver
 * @uses \Toolkit\LocGuard\Filesystem\PhpFileFinder
 * @uses \Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy
 * @uses \Toolkit\LocGuard\Filesystem\PhpPathFileCollector
 * @uses \Toolkit\LocGuard\LocGuardException
 * @uses \Toolkit\TreeGuard\Analysis\AnalysisResult
 * @uses \Toolkit\TreeGuard\Analysis\CaseConventionMatcher
 * @uses \Toolkit\TreeGuard\Analysis\ChildCountInspector
 * @uses \Toolkit\TreeGuard\Analysis\DepthInspector
 * @uses \Toolkit\TreeGuard\Analysis\DirNameInspector
 * @uses \Toolkit\TreeGuard\Analysis\DirectoryPatternMatcher
 * @uses \Toolkit\TreeGuard\Analysis\DirectoryRuleInspector
 * @uses \Toolkit\TreeGuard\Analysis\EmptyDirectoryInspector
 * @uses \Toolkit\TreeGuard\Analysis\FileNameInspector
 * @uses \Toolkit\TreeGuard\Analysis\RequiredFileInspector
 * @uses \Toolkit\TreeGuard\Analysis\TotalFileCountInspector
 * @uses \Toolkit\TreeGuard\Analysis\TreeGuardAnalyzer
 * @uses \Toolkit\TreeGuard\Analysis\Violation
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 * @uses \Toolkit\TreeGuard\Config\TreeGuardConfig
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListingReader
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryTreeScanner
 * @uses \Toolkit\TreeGuard\Filesystem\PathInclusionPolicy
 * @uses \Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver
 * @uses \Toolkit\TreeGuard\TreeGuardException
 */
#[CoversClass(\Toolkit\Guard\Execution\SourceChecks::class)]
#[UsesClass(\Toolkit\DocGuard\Analysis\AnalysisResult::class)]
#[UsesClass(\Toolkit\DocGuard\Analysis\DocGuardAnalyzer::class)]
#[UsesClass(\Toolkit\DocGuard\Analysis\HeadingHunkClassifier::class)]
#[UsesClass(\Toolkit\DocGuard\Analysis\HeadingSequenceAligner::class)]
#[UsesClass(\Toolkit\DocGuard\Analysis\HeadingStructureComparator::class)]
#[UsesClass(\Toolkit\DocGuard\Analysis\UndeclaredDocumentInspector::class)]
#[UsesClass(\Toolkit\DocGuard\Analysis\Violation::class)]
#[UsesClass(\Toolkit\DocGuard\Analysis\ViolationFactory::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DeclaredHeading::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocGuardConfig::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocumentConfig::class)]
#[UsesClass(\Toolkit\DocGuard\DocGuardException::class)]
#[UsesClass(\Toolkit\DocGuard\Filesystem\DocGuardPathResolver::class)]
#[UsesClass(\Toolkit\DocGuard\Filesystem\MarkdownFileFinder::class)]
#[UsesClass(\Toolkit\DocGuard\Filesystem\MarkdownFileReader::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\AtxHeadingMatcher::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\BlockLineScanner::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\BlockMarkerMatcher::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\Fence::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\FenceMatcher::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\Heading::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\HeadingParser::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\HeadingTextNormalizer::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\HtmlBlockMatcher::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\LineIndentation::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\LineScanner::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\MarkdownLineSplitter::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\ParserState::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\SetextUnderlineMatcher::class)]
#[UsesClass(\Toolkit\Guard\Config\Configuration::class)]
#[UsesClass(\Toolkit\Guard\Config\MetricsReader::class)]
#[UsesClass(\Toolkit\Guard\Config\Schema::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
#[UsesClass(\Toolkit\Guard\Policy\Rule::class)]
#[UsesClass(\Toolkit\Guard\Reporting\Finding::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\AnalysisResult::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\ApplyRuleMatcher::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeDeclarationReader::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetric::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricCollector::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricLimit::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\ClassLikeMetric\ClassLikeMetricViolationBuilder::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityCalculator::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\Complexity\CyclomaticComplexityState::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\Complexity\CyclomaticDecisionWeight::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FileAnalysis::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FileMetric\FileMetric::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FileMetric\FileMetricViolationBuilder::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FilePolicyAssigner::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FilePolicyAssignment::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FunctionMetric\ArrowExpressionBoundary::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FunctionMetric\ArrowFunctionMetricReader::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FunctionMetric\BlockFunctionMetricReader::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FunctionMetric\FunctionBodyLocator::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FunctionMetric\FunctionComplexityViolationBuilder::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FunctionMetric\FunctionLineViolationBuilder::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetric::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricCollector::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricComplexityAssigner::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricLineCollector::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FunctionMetric\FunctionMetricViolationBuilder::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FunctionMetric\FunctionNameReader::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FunctionMetric\FunctionScanState::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\FunctionMetric\NestedFunctionMetricRange::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\LocGuardAnalyzer::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\PhpFileAnalyzer::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\Token\ClassLikeTokenMatcher::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\Token\CodeTokenLineResolver::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\Token\PhpTokenNavigator::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\Token\TokenLineCounter::class)]
#[UsesClass(\Toolkit\LocGuard\Analysis\Violation::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ConfigKeyValidator::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ConfigScalarReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ConfigStringListReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\LimitConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\LimitConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\LocGuardConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyPolicyUsageValidator::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyRuleConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyRuleConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyRuleListConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyDefinition::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyListConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyResolver::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ReportConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ScanConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Filesystem\FilePathPatternMatcher::class)]
#[UsesClass(\Toolkit\LocGuard\Filesystem\LocGuardPathResolver::class)]
#[UsesClass(\Toolkit\LocGuard\Filesystem\PhpFileFinder::class)]
#[UsesClass(\Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy::class)]
#[UsesClass(\Toolkit\LocGuard\Filesystem\PhpPathFileCollector::class)]
#[UsesClass(\Toolkit\LocGuard\LocGuardException::class)]
#[UsesClass(\Toolkit\TreeGuard\Analysis\AnalysisResult::class)]
#[UsesClass(\Toolkit\TreeGuard\Analysis\CaseConventionMatcher::class)]
#[UsesClass(\Toolkit\TreeGuard\Analysis\ChildCountInspector::class)]
#[UsesClass(\Toolkit\TreeGuard\Analysis\DepthInspector::class)]
#[UsesClass(\Toolkit\TreeGuard\Analysis\DirNameInspector::class)]
#[UsesClass(\Toolkit\TreeGuard\Analysis\DirectoryPatternMatcher::class)]
#[UsesClass(\Toolkit\TreeGuard\Analysis\DirectoryRuleInspector::class)]
#[UsesClass(\Toolkit\TreeGuard\Analysis\EmptyDirectoryInspector::class)]
#[UsesClass(\Toolkit\TreeGuard\Analysis\FileNameInspector::class)]
#[UsesClass(\Toolkit\TreeGuard\Analysis\RequiredFileInspector::class)]
#[UsesClass(\Toolkit\TreeGuard\Analysis\TotalFileCountInspector::class)]
#[UsesClass(\Toolkit\TreeGuard\Analysis\TreeGuardAnalyzer::class)]
#[UsesClass(\Toolkit\TreeGuard\Analysis\Violation::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\RuleConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\TreeGuardConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\Filesystem\DirectoryListing::class)]
#[UsesClass(\Toolkit\TreeGuard\Filesystem\DirectoryListingReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Filesystem\DirectoryTreeScanner::class)]
#[UsesClass(\Toolkit\TreeGuard\Filesystem\PathInclusionPolicy::class)]
#[UsesClass(\Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver::class)]
#[UsesClass(\Toolkit\TreeGuard\TreeGuardException::class)]
final class SourceChecksTest extends TestCase
{
    public function testCheckKeepsManualSourceViolationsRequired(): void
    {
        $root = sys_get_temp_dir() . '/guard-metrics-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/Long.php', "<?php\n\n\n\n");
        $metrics = (new \Toolkit\Guard\Config\MetricsReader())->read(['source' => ['.'], 'profiles' => ['standard' => ['limits' => ['file' => ['lines' => 1]]]], 'default' => 'standard'], $root);
        $config = new \Toolkit\Guard\Config\Configuration($root, $metrics, null, null, []);
        $findings = (new \Toolkit\Guard\Execution\SourceChecks())->check($config);
        self::assertNotEmpty($findings);
        self::assertSame('required', $findings[0]->level);
    }

}
