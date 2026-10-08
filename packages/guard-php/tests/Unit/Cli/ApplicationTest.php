<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use FilesystemIterator;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @covers \Guard\Cli\Application
 * @uses \Guard\Cli\ClosureOutput
 * @uses \Guard\Cli\Command\FixCommand
 * @uses \Guard\Cli\Command\CheckCommand
 * @uses \Guard\Cli\FormatDetector
 * @uses \Guard\Cli\Command\GuardCommand
 * @uses \Guard\Cli\Command\InitCommand
 * @uses \Guard\Cli\GuardConsole
 * @uses \Guard\Cli\PolicyRun
 * @uses \Guard\Collect\Collector
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Collect\Filesystem\DirectoryTraversal
 * @uses \Guard\Collect\Filesystem\Discovery
 * @uses \Guard\Input\Entry
 * @uses \Guard\Collect\Filesystem\NativeFilesystem
 * @uses \Guard\Input\Path
 * @uses \Guard\Collect\Filesystem\PatternRoots
 * @uses \Guard\Collect\Filesystem\QueryResult
 * @uses \Guard\Collect\Filesystem\Route
 * @uses \Guard\Collect\Filesystem\SelectionRoots
 * @uses \Guard\Collect\Filesystem\Snapshot
 * @uses \Guard\Collect\Filesystem\WalkQueue
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Collect\Matching\GlobMatcher
 * @uses \Guard\Input\PathPatternMatcher
 * @uses \Guard\Collect\Matching\SelectionFilter
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Policy\Assignment\ApplyRuleMatcher
 * @uses \Guard\Policy\Assignment\FilePolicyAssigner
 * @uses \Guard\Policy\Assignment\FilePolicyAssignment
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\ConfigurationLoader
 * @uses \Guard\Config\DocumentMerger
 * @uses \Guard\Config\ImportResolver
 * @uses \Guard\Policy\Definition\ApplyConfig
 * @uses \Guard\Config\Profile\ApplyConfigReader
 * @uses \Guard\Config\Profile\ApplyPolicyUsageValidator
 * @uses \Guard\Policy\Definition\ApplyRuleConfig
 * @uses \Guard\Config\Profile\ApplyRuleConfigReader
 * @uses \Guard\Config\Profile\ApplyRuleListConfigReader
 * @uses \Guard\Policy\Definition\PolicyConfig
 * @uses \Guard\Config\Profile\PolicyConfigReader
 * @uses \Guard\Config\Profile\PolicyDefinition
 * @uses \Guard\Config\Profile\PolicyListConfigReader
 * @uses \Guard\Config\Profile\PolicyResolver
 * @uses \Guard\Config\Reader\DeclaredHeadingReader
 * @uses \Guard\Config\Reader\DirectoryPolicyReader
 * @uses \Guard\Config\Reader\DirectoryRuleConfigReader
 * @uses \Guard\Config\Reader\DirectoryRuleListConfigReader
 * @uses \Guard\Config\Reader\DocumentConfigReader
 * @uses \Guard\Config\Reader\DocumentListConfigReader
 * @uses \Guard\Config\Reader\ExtensionConfigReader
 * @uses \Guard\Config\Reader\HeadingPolicyReader
 * @uses \Guard\Config\Reader\LimitConfigReader
 * @uses \Guard\Config\Reader\MetricPolicyReader
 * @uses \Guard\Config\Reader\ScanConfigReader
 * @uses \Guard\Config\RuleReader
 * @uses \Guard\Config\Schema
 * @uses \Guard\Config\Validation\DirectoryConfigScalarReader
 * @uses \Guard\Config\Validation\DirectoryConfigStringListReader
 * @uses \Guard\Config\Validation\HeadingConfigKeyValidator
 * @uses \Guard\Config\Validation\HeadingConfigStringListReader
 * @uses \Guard\Config\Validation\MetricConfigKeyValidator
 * @uses \Guard\Config\Validation\MetricConfigScalarReader
 * @uses \Guard\Config\Validation\MetricConfigStringListReader
 * @uses \Guard\Policy\Definition\DeclaredHeading
 * @uses \Guard\Policy\Definition\DirectoryRuleConfig
 * @uses \Guard\Policy\Definition\DocumentConfig
 * @uses \Guard\Policy\Definition\DocumentationConfig
 * @uses \Guard\Policy\Definition\LimitConfig
 * @uses \Guard\Policy\Definition\MetricsConfig
 * @uses \Guard\Policy\Definition\ScanConfig
 * @uses \Guard\Policy\Definition\StructureConfig
 * @uses \Guard\Structure\Document\DataDocument
 * @uses \Guard\Structure\Document\DocumentFailure
 * @uses \Guard\Structure\Document\DocumentNode
 * @uses \Guard\Structure\Document\Json5Reader
 * @uses \Guard\Structure\Document\PhpConfigReader
 * @uses \Guard\Structure\Document\PhpDocument
 * @uses \Guard\Structure\Document\Pointer
 * @uses \Guard\Structure\Document\Selection
 * @uses \Guard\Structure\Document\TomlEncoder
 * @uses \Guard\Structure\Document\XmlDocument
 * @uses \Guard\Repair\AtomicWriter
 * @uses \Guard\Repair\ChangeSet
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Execution\Pipeline
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Collect\Filesystem\TargetPath
 * @uses \Guard\Execution\Registry
 * @uses \Guard\Config\ComponentLoader
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Config\Project\Initializer
 * @uses \Guard\Config\Project\LegacyMigration
 * @uses \Guard\Config\Project\Legacy\DirectoryConfiguration
 * @uses \Guard\Config\Project\Legacy\DirectoryReportConfig
 * @uses \Guard\Config\Project\Legacy\DirectoryReportReader
 * @uses \Guard\Config\Project\Legacy\HeadingConfiguration
 * @uses \Guard\Config\Project\Legacy\HeadingReportConfig
 * @uses \Guard\Config\Project\Legacy\HeadingReportReader
 * @uses \Guard\Config\Project\Legacy\MetricConfiguration
 * @uses \Guard\Config\Project\Legacy\MetricReportConfig
 * @uses \Guard\Config\Project\Legacy\MetricReportReader
 * @uses \Guard\Config\Project\PresetCatalog
 * @uses \Guard\Config\Project\PresetOverrides
 * @uses \Guard\Config\Project\PresetSelector
 * @uses \Guard\Config\Project\Recommendations
 * @uses \Guard\Config\Project\ToolDetector
 * @uses \Guard\Policy\Comparison\HeadingHunkClassifier
 * @uses \Guard\Policy\Comparison\HeadingSequenceAligner
 * @uses \Guard\Policy\Comparison\HeadingStructureComparator
 * @uses \Guard\Structure\Document\Constraint
 * @uses \Guard\Policy\DirectoryEntries
 * @uses \Guard\Policy\FieldConstraints
 * @uses \Guard\Policy\HeadingStructure
 * @uses \Guard\Policy\Inspection\CaseConventionMatcher
 * @uses \Guard\Policy\Inspection\ChildCountInspector
 * @uses \Guard\Policy\Inspection\DepthInspector
 * @uses \Guard\Policy\Inspection\DirNameInspector
 * @uses \Guard\Policy\Inspection\DirectoryPatternMatcher
 * @uses \Guard\Policy\Inspection\DirectoryRuleInspector
 * @uses \Guard\Policy\Inspection\EmptyDirectoryInspector
 * @uses \Guard\Policy\Inspection\FileNameInspector
 * @uses \Guard\Policy\Inspection\RequiredFileInspector
 * @uses \Guard\Policy\Inspection\TotalFileCountInspector
 * @uses \Guard\Policy\Limit\ClassLikeMetricLimit
 * @uses \Guard\Policy\Limit\ClassLikeMetricViolationBuilder
 * @uses \Guard\Policy\Limit\FileMetricViolationBuilder
 * @uses \Guard\Policy\Limit\FunctionComplexityViolationBuilder
 * @uses \Guard\Policy\Limit\FunctionLineViolationBuilder
 * @uses \Guard\Policy\Limit\FunctionMetricViolationBuilder
 * @uses \Guard\Policy\Limit\MetricLimitInspector
 * @uses \Guard\Policy\MetricLimits
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Policy\RuleEvaluator
 * @uses \Guard\Policy\Diagnostic\DirectoryViolation
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Policy\Diagnostic\HeadingViolation
 * @uses \Guard\Policy\Diagnostic\HeadingViolationFactory
 * @uses \Guard\Policy\Diagnostic\MetricViolation
 * @uses \Guard\Reporting\ChangeDiff
 * @uses \Guard\Policy\Diagnostic\RuleMessages
 * @uses \Guard\Reporting\Reporter
 * @uses \Guard\Structure\DocumentStructurer
 * @uses \Guard\Structure\Markdown\AtxHeadingMatcher
 * @uses \Guard\Structure\Markdown\BlockMarkerMatcher
 * @uses \Guard\Structure\Markdown\Block\BlockLineScanner
 * @uses \Guard\Structure\Markdown\Fence
 * @uses \Guard\Structure\Markdown\FenceMatcher
 * @uses \Guard\Structure\Markdown\Heading
 * @uses \Guard\Structure\Markdown\HeadingList
 * @uses \Guard\Structure\Markdown\HeadingParser
 * @uses \Guard\Structure\Markdown\HeadingStructurer
 * @uses \Guard\Structure\Markdown\HeadingTextNormalizer
 * @uses \Guard\Structure\Markdown\HtmlBlockMatcher
 * @uses \Guard\Structure\Markdown\LineIndentation
 * @uses \Guard\Structure\Markdown\LineScanner
 * @uses \Guard\Structure\Markdown\MarkdownLineSplitter
 * @uses \Guard\Structure\Markdown\ParserState
 * @uses \Guard\Structure\Markdown\SetextUnderlineMatcher
 * @uses \Guard\Structure\ParsedDocument
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeMetricParser
 * @uses \Guard\Structure\Php\Complexity\CyclomaticComplexityCalculator
 * @uses \Guard\Structure\Php\Complexity\CyclomaticComplexityState
 * @uses \Guard\Structure\Php\Complexity\CyclomaticDecisionWeight
 * @uses \Guard\Structure\Php\FileMetric\FileMetric
 * @uses \Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary
 * @uses \Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionBodyLocator
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionLineParser
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetricParser
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionNameReader
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionScanState
 * @uses \Guard\Structure\Php\FunctionMetric\NestedFunctionMetricRange
 * @uses \Guard\Structure\Php\MetricParser
 * @uses \Guard\Structure\Php\SourceMetrics
 * @uses \Guard\Structure\Php\TokenParser
 * @uses \Guard\Structure\Php\Token\ClassLikeTokenMatcher
 * @uses \Guard\Structure\Php\Token\CodeTokenLineResolver
 * @uses \Guard\Structure\Php\Token\PhpTokenNavigator
 * @uses \Guard\Structure\Php\Token\TokenLineCounter
 * @uses \Guard\Structure\Php\Tokens
 * @uses \Guard\Structure\Source
 * @uses \Guard\Cli\Command\RulesCommand
 * @uses \Guard\Policy\Diagnostic\FieldMessage
 * @uses \Guard\Cli\CheckRun
 * @uses \Guard\Cli\BaselineFile
 * @uses \Guard\Cli\Command\BaselineCommand
 * @uses \Guard\Reporting\Baseline
 * @uses \Guard\Reporting\BaselineMatch
 * @uses \Guard\Reporting\Filtering\FindingFilter
 * @uses \Guard\Reporting\Filtering\ReportScope
 */
#[CoversClass(\Guard\Cli\Application::class)]
#[UsesClass(\Guard\Cli\ClosureOutput::class)]
#[UsesClass(\Guard\Cli\Command\FixCommand::class)]
#[UsesClass(\Guard\Cli\Command\CheckCommand::class)]
#[UsesClass(\Guard\Cli\Command\GuardCommand::class)]
#[UsesClass(\Guard\Cli\Command\InitCommand::class)]
#[UsesClass(\Guard\Cli\GuardConsole::class)]
#[UsesClass(\Guard\Cli\PolicyRun::class)]
#[UsesClass(\Guard\Collect\Collector::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\DirectoryTraversal::class)]
#[UsesClass(\Guard\Collect\Filesystem\Discovery::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Collect\Filesystem\NativeFilesystem::class)]
#[UsesClass(\Guard\Input\Path::class)]
#[UsesClass(\Guard\Collect\Filesystem\PatternRoots::class)]
#[UsesClass(\Guard\Collect\Filesystem\QueryResult::class)]
#[UsesClass(\Guard\Collect\Filesystem\Route::class)]
#[UsesClass(\Guard\Collect\Filesystem\SelectionRoots::class)]
#[UsesClass(\Guard\Collect\Filesystem\Snapshot::class)]
#[UsesClass(\Guard\Collect\Filesystem\WalkQueue::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Collect\Matching\GlobMatcher::class)]
#[UsesClass(\Guard\Input\PathPatternMatcher::class)]
#[UsesClass(\Guard\Collect\Matching\SelectionFilter::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Policy\Assignment\ApplyRuleMatcher::class)]
#[UsesClass(\Guard\Policy\Assignment\FilePolicyAssigner::class)]
#[UsesClass(\Guard\Policy\Assignment\FilePolicyAssignment::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\ConfigurationLoader::class)]
#[UsesClass(\Guard\Config\DocumentMerger::class)]
#[UsesClass(\Guard\Config\ImportResolver::class)]
#[UsesClass(\Guard\Policy\Definition\ApplyConfig::class)]
#[UsesClass(\Guard\Config\Profile\ApplyConfigReader::class)]
#[UsesClass(\Guard\Config\Profile\ApplyPolicyUsageValidator::class)]
#[UsesClass(\Guard\Policy\Definition\ApplyRuleConfig::class)]
#[UsesClass(\Guard\Config\Profile\ApplyRuleConfigReader::class)]
#[UsesClass(\Guard\Config\Profile\ApplyRuleListConfigReader::class)]
#[UsesClass(\Guard\Policy\Definition\PolicyConfig::class)]
#[UsesClass(\Guard\Config\Profile\PolicyConfigReader::class)]
#[UsesClass(\Guard\Config\Profile\PolicyDefinition::class)]
#[UsesClass(\Guard\Config\Profile\PolicyListConfigReader::class)]
#[UsesClass(\Guard\Config\Profile\PolicyResolver::class)]
#[UsesClass(\Guard\Config\Reader\DeclaredHeadingReader::class)]
#[UsesClass(\Guard\Config\Reader\DirectoryPolicyReader::class)]
#[UsesClass(\Guard\Config\Reader\DirectoryRuleConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\DirectoryRuleListConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\DocumentConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\DocumentListConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\ExtensionConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\HeadingPolicyReader::class)]
#[UsesClass(\Guard\Config\Reader\LimitConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\MetricPolicyReader::class)]
#[UsesClass(\Guard\Config\Reader\ScanConfigReader::class)]
#[UsesClass(\Guard\Config\RuleReader::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Config\Validation\DirectoryConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Validation\DirectoryConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Validation\HeadingConfigKeyValidator::class)]
#[UsesClass(\Guard\Config\Validation\HeadingConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Validation\MetricConfigKeyValidator::class)]
#[UsesClass(\Guard\Config\Validation\MetricConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Validation\MetricConfigStringListReader::class)]
#[UsesClass(\Guard\Policy\Definition\DeclaredHeading::class)]
#[UsesClass(\Guard\Policy\Definition\DirectoryRuleConfig::class)]
#[UsesClass(\Guard\Policy\Definition\DocumentConfig::class)]
#[UsesClass(\Guard\Policy\Definition\DocumentationConfig::class)]
#[UsesClass(\Guard\Policy\Definition\LimitConfig::class)]
#[UsesClass(\Guard\Policy\Definition\MetricsConfig::class)]
#[UsesClass(\Guard\Policy\Definition\ScanConfig::class)]
#[UsesClass(\Guard\Policy\Definition\StructureConfig::class)]
#[UsesClass(\Guard\Structure\Document\DataDocument::class)]
#[UsesClass(\Guard\Structure\Document\DocumentFailure::class)]
#[UsesClass(\Guard\Structure\Document\DocumentNode::class)]
#[UsesClass(\Guard\Structure\Document\Json5Reader::class)]
#[UsesClass(\Guard\Structure\Document\PhpConfigReader::class)]
#[UsesClass(\Guard\Structure\Document\PhpDocument::class)]
#[UsesClass(\Guard\Structure\Document\Pointer::class)]
#[UsesClass(\Guard\Structure\Document\Selection::class)]
#[UsesClass(\Guard\Structure\Document\TomlEncoder::class)]
#[UsesClass(\Guard\Structure\Document\XmlDocument::class)]
#[UsesClass(\Guard\Repair\AtomicWriter::class)]
#[UsesClass(\Guard\Repair\ChangeSet::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Execution\Pipeline::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Collect\Filesystem\TargetPath::class)]
#[UsesClass(\Guard\Execution\Registry::class)]
#[UsesClass(\Guard\Config\ComponentLoader::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(\Guard\Config\Project\Initializer::class)]
#[UsesClass(\Guard\Config\Project\LegacyMigration::class)]
#[UsesClass(\Guard\Config\Project\Legacy\DirectoryConfiguration::class)]
#[UsesClass(\Guard\Config\Project\Legacy\DirectoryReportConfig::class)]
#[UsesClass(\Guard\Config\Project\Legacy\DirectoryReportReader::class)]
#[UsesClass(\Guard\Config\Project\Legacy\HeadingConfiguration::class)]
#[UsesClass(\Guard\Config\Project\Legacy\HeadingReportConfig::class)]
#[UsesClass(\Guard\Config\Project\Legacy\HeadingReportReader::class)]
#[UsesClass(\Guard\Config\Project\Legacy\MetricConfiguration::class)]
#[UsesClass(\Guard\Config\Project\Legacy\MetricReportConfig::class)]
#[UsesClass(\Guard\Config\Project\Legacy\MetricReportReader::class)]
#[UsesClass(\Guard\Config\Project\PresetCatalog::class)]
#[UsesClass(\Guard\Config\Project\PresetOverrides::class)]
#[UsesClass(\Guard\Config\Project\PresetSelector::class)]
#[UsesClass(\Guard\Config\Project\Recommendations::class)]
#[UsesClass(\Guard\Config\Project\ToolDetector::class)]
#[UsesClass(\Guard\Policy\Comparison\HeadingHunkClassifier::class)]
#[UsesClass(\Guard\Policy\Comparison\HeadingSequenceAligner::class)]
#[UsesClass(\Guard\Policy\Comparison\HeadingStructureComparator::class)]
#[UsesClass(\Guard\Structure\Document\Constraint::class)]
#[UsesClass(\Guard\Policy\DirectoryEntries::class)]
#[UsesClass(\Guard\Policy\FieldConstraints::class)]
#[UsesClass(\Guard\Policy\HeadingStructure::class)]
#[UsesClass(\Guard\Policy\Inspection\CaseConventionMatcher::class)]
#[UsesClass(\Guard\Policy\Inspection\ChildCountInspector::class)]
#[UsesClass(\Guard\Policy\Inspection\DepthInspector::class)]
#[UsesClass(\Guard\Policy\Inspection\DirNameInspector::class)]
#[UsesClass(\Guard\Policy\Inspection\DirectoryPatternMatcher::class)]
#[UsesClass(\Guard\Policy\Inspection\DirectoryRuleInspector::class)]
#[UsesClass(\Guard\Policy\Inspection\EmptyDirectoryInspector::class)]
#[UsesClass(\Guard\Policy\Inspection\FileNameInspector::class)]
#[UsesClass(\Guard\Policy\Inspection\RequiredFileInspector::class)]
#[UsesClass(\Guard\Policy\Inspection\TotalFileCountInspector::class)]
#[UsesClass(\Guard\Policy\Limit\ClassLikeMetricLimit::class)]
#[UsesClass(\Guard\Policy\Limit\ClassLikeMetricViolationBuilder::class)]
#[UsesClass(\Guard\Policy\Limit\FileMetricViolationBuilder::class)]
#[UsesClass(\Guard\Policy\Limit\FunctionComplexityViolationBuilder::class)]
#[UsesClass(\Guard\Policy\Limit\FunctionLineViolationBuilder::class)]
#[UsesClass(\Guard\Policy\Limit\FunctionMetricViolationBuilder::class)]
#[UsesClass(\Guard\Policy\Limit\MetricLimitInspector::class)]
#[UsesClass(\Guard\Policy\MetricLimits::class)]
#[UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Policy\RuleEvaluator::class)]
#[UsesClass(\Guard\Policy\Diagnostic\DirectoryViolation::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
#[UsesClass(\Guard\Policy\Diagnostic\HeadingViolation::class)]
#[UsesClass(\Guard\Policy\Diagnostic\HeadingViolationFactory::class)]
#[UsesClass(\Guard\Policy\Diagnostic\MetricViolation::class)]
#[UsesClass(\Guard\Reporting\Reporter::class)]
#[UsesClass(\Guard\Structure\DocumentStructurer::class)]
#[UsesClass(\Guard\Structure\Markdown\AtxHeadingMatcher::class)]
#[UsesClass(\Guard\Structure\Markdown\BlockMarkerMatcher::class)]
#[UsesClass(\Guard\Structure\Markdown\Block\BlockLineScanner::class)]
#[UsesClass(\Guard\Structure\Markdown\Fence::class)]
#[UsesClass(\Guard\Structure\Markdown\FenceMatcher::class)]
#[UsesClass(\Guard\Structure\Markdown\Heading::class)]
#[UsesClass(\Guard\Structure\Markdown\HeadingList::class)]
#[UsesClass(\Guard\Structure\Markdown\HeadingParser::class)]
#[UsesClass(\Guard\Structure\Markdown\HeadingStructurer::class)]
#[UsesClass(\Guard\Structure\Markdown\HeadingTextNormalizer::class)]
#[UsesClass(\Guard\Structure\Markdown\HtmlBlockMatcher::class)]
#[UsesClass(\Guard\Structure\Markdown\LineIndentation::class)]
#[UsesClass(\Guard\Structure\Markdown\LineScanner::class)]
#[UsesClass(\Guard\Structure\Markdown\MarkdownLineSplitter::class)]
#[UsesClass(\Guard\Structure\Markdown\ParserState::class)]
#[UsesClass(\Guard\Structure\Markdown\SetextUnderlineMatcher::class)]
#[UsesClass(\Guard\Structure\ParsedDocument::class)]
#[UsesClass(\Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader::class)]
#[UsesClass(\Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric::class)]
#[UsesClass(\Guard\Structure\Php\ClassLikeMetric\ClassLikeMetricParser::class)]
#[UsesClass(\Guard\Structure\Php\Complexity\CyclomaticComplexityCalculator::class)]
#[UsesClass(\Guard\Structure\Php\Complexity\CyclomaticComplexityState::class)]
#[UsesClass(\Guard\Structure\Php\Complexity\CyclomaticDecisionWeight::class)]
#[UsesClass(\Guard\Structure\Php\FileMetric\FileMetric::class)]
#[UsesClass(\Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary::class)]
#[UsesClass(\Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader::class)]
#[UsesClass(\Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader::class)]
#[UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionBodyLocator::class)]
#[UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionLineParser::class)]
#[UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionMetric::class)]
#[UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionMetricComplexityAssigner::class)]
#[UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionMetricParser::class)]
#[UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionNameReader::class)]
#[UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionScanState::class)]
#[UsesClass(\Guard\Structure\Php\FunctionMetric\NestedFunctionMetricRange::class)]
#[UsesClass(\Guard\Structure\Php\MetricParser::class)]
#[UsesClass(\Guard\Structure\Php\SourceMetrics::class)]
#[UsesClass(\Guard\Structure\Php\TokenParser::class)]
#[UsesClass(\Guard\Structure\Php\Token\ClassLikeTokenMatcher::class)]
#[UsesClass(\Guard\Structure\Php\Token\CodeTokenLineResolver::class)]
#[UsesClass(\Guard\Structure\Php\Token\PhpTokenNavigator::class)]
#[UsesClass(\Guard\Structure\Php\Token\TokenLineCounter::class)]
#[UsesClass(\Guard\Structure\Php\Tokens::class)]
#[UsesClass(\Guard\Structure\Source::class)]
#[UsesClass(\Guard\Reporting\ChangeDiff::class)]
#[UsesClass(\Guard\Policy\Diagnostic\RuleMessages::class)]
#[UsesClass(\Guard\Cli\FormatDetector::class)]
#[UsesClass(\Guard\Cli\Command\RulesCommand::class)]
#[UsesClass(\Guard\Policy\Diagnostic\FieldMessage::class)]
#[UsesClass(\Guard\Cli\CheckRun::class)]
#[UsesClass(\Guard\Cli\BaselineFile::class)]
#[UsesClass(\Guard\Cli\Command\BaselineCommand::class)]
#[UsesClass(\Guard\Reporting\Baseline::class)]
#[UsesClass(\Guard\Reporting\BaselineMatch::class)]
#[UsesClass(\Guard\Reporting\Filtering\FindingFilter::class)]
#[UsesClass(\Guard\Reporting\Filtering\ReportScope::class)]
final class ApplicationTest extends TestCase
{
    public function testInitRejectsNewDocsIncludingEmptyDirectories(): void
    {
        $root = sys_get_temp_dir() . '/guard-disable-doc-' . uniqid();
        mkdir($root . '/vendor/example/docs', 0777, true);
        mkdir($root . '/packages/example/docs', 0777, true);
        $output = '';
        $app = new \Guard\Cli\Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(0, $app->run(['init']));
        self::assertStringContainsString('disable-doc.yaml', (string) file_get_contents($root . '/guard.yaml'));
        file_put_contents($root . '/README.md', "# Project\n");
        self::assertSame(0, $app->run(['check']));
        mkdir($root . '/docs');
        $output = '';
        self::assertSame(1, $app->run(['check']));
        self::assertStringContainsString('structure.denied_dir', $output);
        self::assertStringContainsString('Directory "docs" matches denied pattern "docs". Rename or remove it.', $output);
        file_put_contents($root . '/docs/guide.md', "# Guide\n");
        self::assertSame(1, $app->run(['check']));
        self::assertSame(1, $app->run(['fix', '--dry-run']));
        self::assertSame(1, $app->run(['fix']));
        self::assertSame("# Guide\n", file_get_contents($root . '/docs/guide.md'));
    }

    public function testInitAllowsExistingDocs(): void
    {
        $root = sys_get_temp_dir() . '/guard-allow-doc-' . uniqid();
        mkdir($root . '/docs', 0777, true);
        file_put_contents($root . '/docs/guide.md', "# Guide\n");
        $output = '';
        $app = new \Guard\Cli\Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(0, $app->run(['init']));
        self::assertStringNotContainsString('disable-doc.yaml', (string) file_get_contents($root . '/guard.yaml'));
        self::assertSame(0, $app->run(['check']));
        self::assertSame("# Guide\n", file_get_contents($root . '/docs/guide.md'));
    }

    /**
     * @dataProvider providerStructureImportOrders
     */
    #[DataProvider('providerStructureImportOrders')]
    public function testDisableDocCombinesWithStructureInEitherImportOrder(string $imports): void
    {
        $root = sys_get_temp_dir() . '/guard-disable-doc-structure-' . uniqid();
        mkdir($root . '/docs', 0777, true);
        mkdir($root . '/scripts');
        mkdir($root . '/vendor/example/docs', 0777, true);
        $output = '';
        $app = new \Guard\Cli\Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(0, $app->run(['init', '--import=' . $imports]));
        $output = '';
        self::assertSame(1, $app->run(['check']));
        self::assertStringContainsString('Directory "docs" matches denied pattern "docs".', $output);
        self::assertStringContainsString('Directory "scripts" matches denied pattern "scripts".', $output);
        self::assertStringNotContainsString('vendor/example/docs', $output);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function providerStructureImportOrders(): iterable
    {
        yield 'structure first' => ['structure,disable-doc'];
        yield 'disable-doc first' => ['disable-doc,structure'];
    }

    public function testInitDisablesDocsWhenLegacyTreeScansOnlySourceDirectories(): void
    {
        $root = sys_get_temp_dir() . '/guard-legacy-disable-doc-' . uniqid();
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Example.php', "<?php\n");
        file_put_contents($root . '/tree.yaml', "paths: [src]\nrules: [{path: 'src/**', allow: ['*.php']}]\n");
        $output = '';
        $app = new \Guard\Cli\Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(0, $app->run(['init']));
        self::assertSame(0, $app->run(['check']));
        mkdir($root . '/docs');
        file_put_contents($root . '/src/notes.txt', 'notes');
        $output = '';
        self::assertSame(1, $app->run(['check']));
        self::assertStringContainsString('Directory "docs" matches denied pattern "docs".', $output);
        self::assertStringContainsString('src/notes.txt', $output);
        self::assertStringContainsString('structure.disallowed_file', $output);
    }
    /**
     * @throws JsonException
     */
    public function testRunChecksDryRunsAppliesAndIsIdempotent(): void
    {
        $root = sys_get_temp_dir() . '/guard-cli-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/app.json', '{"workers":0,"other":"keep"}');
        file_put_contents($root . '/guard.yaml', "version: 1\nconfiguration:\n  - id: workers\n    file: app.json\n    select: /workers\n    assert: {min: 1}\n    repair: 1\n");
        $output = '';
        $app = new \Guard\Cli\Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(1, $app->run(['check']));
        self::assertSame('{"workers":0,"other":"keep"}', file_get_contents($root . '/app.json'));
        self::assertSame(0, $app->run(['fix', '--dry-run']));
        self::assertSame('{"workers":0,"other":"keep"}', file_get_contents($root . '/app.json'));
        self::assertSame(0, $app->run(['fix']));
        self::assertSame(['workers' => 1, 'other' => 'keep'], json_decode((string) file_get_contents($root . '/app.json'), true, 512, JSON_THROW_ON_ERROR));
        $after = file_get_contents($root . '/app.json');
        self::assertSame(0, $app->run(['fix']));
        self::assertSame($after, file_get_contents($root . '/app.json'));
        self::assertStringContainsString('workers', $output);
    }

    /**
     * @throws JsonException
     */
    public function testCheckRecommendationsWarnWithoutFailing(): void
    {
        $root = sys_get_temp_dir() . '/guard-warning-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/app.json', '{"mode":"B"}');
        file_put_contents($root . '/guard.yaml', "version: 1\nconfiguration:\n  - id: mode\n    file: app.json\n    select: /mode\n    level: recommended\n    assert: {equals: A}\n");
        $output = '';
        $app = new \Guard\Cli\Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(0, $app->run(['check']));
        self::assertStringContainsString('warning:', $output);
        self::assertSame(0, $app->run(['fix']));
        self::assertSame(['mode' => 'A'], json_decode((string) file_get_contents($root . '/app.json'), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testConflictsBlockEveryFileWrite(): void
    {
        $root = sys_get_temp_dir() . '/guard-conflict-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/a.json', '{"mode":"C"}');
        file_put_contents($root . '/b.json', '{"mode":"C"}');
        file_put_contents($root . '/guard.yaml', "version: 1\nconfiguration:\n  - {id: first, file: a.json, select: /mode, assert: {equals: A}}\n  - {id: second, file: b.json, select: /mode, assert: {equals: A}}\n  - {id: conflict, file: b.json, select: /mode, assert: {equals: B}}\n");
        $output = '';
        $app = new \Guard\Cli\Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(1, $app->run(['fix']));
        self::assertSame('{"mode":"C"}', file_get_contents($root . '/a.json'));
        self::assertSame('{"mode":"C"}', file_get_contents($root . '/b.json'));
        self::assertStringContainsString('blocked', $output);
    }

    public function testRunListsTheCommandsForHelpWithoutACommand(): void
    {
        $output = '';
        $app = new \Guard\Cli\Application(sys_get_temp_dir(), static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(0, $app->run(['--help']));
        self::assertStringContainsString('Running guard without a command runs check.', $output);
        self::assertStringContainsString('Available commands:', $output);
        self::assertMatchesRegularExpression('/^  fix +Repair /m', $output);
        self::assertMatchesRegularExpression('/^  check +Check /m', $output);
        self::assertMatchesRegularExpression('/^  init +Create /m', $output);
    }
    public function testRunShowsTheHelpOfOneCommand(): void
    {
        $output = '';
        $app = new \Guard\Cli\Application(sys_get_temp_dir(), static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(0, $app->run(['init', '--help']));
        self::assertStringContainsString('--import=IMPORT', $output);
        self::assertStringNotContainsString('Help:', $output);
    }
    public function testRunReportsTheVersion(): void
    {
        $output = '';
        $app = new \Guard\Cli\Application(sys_get_temp_dir(), static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(0, $app->run(['--version']));
        self::assertSame("guard 1.0.0\n", $output);
    }
    public function testRunRejectsAnUnknownOptionWithAPointerToTheCommandHelp(): void
    {
        $output = '';
        $app = new \Guard\Cli\Application(sys_get_temp_dir(), static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(2, $app->run(['fix', '--bogus']));
        self::assertSame("Guard error: The \"--bogus\" option does not exist.\nRun \"guard fix --help\" to see its options.\n", $output);
    }
    public function testRunReadsAnOptionValueGivenAsTheNextArgument(): void
    {
        $root = sys_get_temp_dir() . '/guard-cli-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/team.yaml', "version: 1\n");
        $output = '';
        $app = new \Guard\Cli\Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(0, $app->run(['check', '-c', 'team.yaml', '--format', 'json']));
        self::assertStringContainsString('"success": true', $output);
    }
    public function testRunKeepsErrorsButNotTheReportWhenQuiet(): void
    {
        $root = sys_get_temp_dir() . '/guard-cli-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/guard.yaml', "version: 1\n");
        $output = '';
        $app = new \Guard\Cli\Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(0, $app->run(['check', '--quiet']));
        self::assertSame('', $output);
        self::assertSame(2, $app->run(['check', '--quiet', '--config=missing.yaml']));
        self::assertStringStartsWith('Guard error: Guard configuration not found:', $output);
        self::assertSame(0, $app->run(['check']));
        self::assertStringEndsWith("Guard passed.\n", $output);
    }
    public function testConsoleHasTheGuardCommands(): void
    {
        $console = (new \Guard\Cli\Application(sys_get_temp_dir()))->console();
        self::assertSame('guard', $console->getName());
        self::assertTrue($console->has('check'));
        self::assertTrue($console->has('fix'));
        self::assertTrue($console->has('init'));
    }
    public function testOutputWritesToTheProcessStreamsWithoutAnInjectedSink(): void
    {
        self::assertInstanceOf(\Symfony\Component\Console\Output\ConsoleOutput::class, (new \Guard\Cli\Application(sys_get_temp_dir()))->output());
    }
    public function testOutputWritesToTheInjectedSink(): void
    {
        $output = '';
        (new \Guard\Cli\Application(sys_get_temp_dir(), static function (string $text) use (&$output): void {
            $output .= $text;
        }))->output()->writeln('<info>raw</info>');
        self::assertSame("raw\n", $output);
    }
    public function testRunMalformedInputPreventsEarlierPlannedWrites(): void
    {
        $root = sys_get_temp_dir() . '/guard-malformed-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/a.json', '{"mode":"B"}');
        file_put_contents($root . '/b.json', '{');
        file_put_contents($root . '/guard.yaml', "version: 1\nconfiguration:\n  - {id: first, file: a.json, select: /mode, assert: {equals: A}}\n  - {id: broken, file: b.json, select: /mode, assert: {equals: A}}\n");
        $output = '';
        $app = new \Guard\Cli\Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(2, $app->run(['fix']));
        self::assertSame('{"mode":"B"}', file_get_contents($root . '/a.json'));
        self::assertSame('{', file_get_contents($root . '/b.json'));
        self::assertStringContainsString('Guard error:', $output);
        self::assertStringContainsString('b.json', $output);
    }

    public function testRunReportsFixabilityMessagesAndDiffsWithoutWritingDuringPreview(): void
    {
        $project = new class (['app.json' => '{"workers":0}', 'guard.yaml' => "version: 1\nconfiguration:\n  - {id: workers, file: app.json, select: /workers, message: Bound concurrency. Set /workers to 2., assert: {equals: 2}}\n  - {id: missing, file: absent.json, select: /x, assert: {equals: 1}}\n"]) {
            public string $root;
            /**
             * @param array<array-key, string> $files
             */
            public function __construct(array $files = [])
            {
                $this->root = sys_get_temp_dir() . '/guard-contract-' . uniqid('', true);
                mkdir($this->root);
                foreach ($files as $path => $source) {
                    $this->write((string) $path, $source);
                }
            }
            public function write(string $path, string $source): void
            {
                $directory = dirname($this->root . '/' . $path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($this->root . '/' . $path, $source);
            }


            public function remove(): void
            {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file instanceof SplFileInfo) {
                        if ($file->isDir() && !$file->isLink()) {
                            rmdir($file->getPathname());
                        } else {
                            unlink($file->getPathname());
                        }
                    }
                }
                rmdir($this->root);
            }
        };
        $output = '';
        $application = new \Guard\Cli\Application($project->root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        try {
            self::assertSame(1, $application->run(['check', '--format=json', '--ansi']));
            self::assertJson($output);
            self::assertStringContainsString('"fixable": true', $output);
            self::assertStringContainsString('"fixable": false', $output);
            self::assertStringContainsString('"message": "Bound concurrency. Set /workers to 2.', $output);
            self::assertStringContainsString('"action": "checked"', $output);
            $output = '';
            self::assertSame(1, $application->run(['fix', '--dry-run', '--format=json']));
            self::assertJson($output);
            self::assertStringContainsString('"action": "blocked"', $output);
            self::assertStringContainsString('-{\\"workers\\":0}', $output);
            self::assertStringContainsString('+    \\"workers\\": 2', $output);
            self::assertSame('{"workers":0}', file_get_contents($project->root . '/app.json'));
            self::assertSame(1, $application->run(['fix', '--format=text']));
            self::assertSame('{"workers":0}', file_get_contents($project->root . '/app.json'));
            self::assertSame(2, $application->run(['apply']));
        } finally {
            $project->remove();
        }
    }

    public function testRunExplicitHumanFormatOverridesAgentDetection(): void
    {
        $project = new class (['guard.yaml' => "version: 1\n"]) {
            public string $root;
            /**
             * @param array<array-key, string> $files
             */
            public function __construct(array $files = [])
            {
                $this->root = sys_get_temp_dir() . '/guard-contract-' . uniqid('', true);
                mkdir($this->root);
                foreach ($files as $path => $source) {
                    $this->write((string) $path, $source);
                }
            }
            public function write(string $path, string $source): void
            {
                $directory = dirname($this->root . '/' . $path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($this->root . '/' . $path, $source);
            }


            public function remove(): void
            {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file instanceof SplFileInfo) {
                        if ($file->isDir() && !$file->isLink()) {
                            rmdir($file->getPathname());
                        } else {
                            unlink($file->getPathname());
                        }
                    }
                }
                rmdir($this->root);
            }
        };
        $before = getenv('AI_AGENT');
        $output = '';
        $application = new \Guard\Cli\Application($project->root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        try {
            putenv('AI_AGENT=test');
            self::assertSame(0, $application->run(['check']));
            self::assertStringNotContainsString('Guard check', $output);
            $output = '';
            self::assertSame(0, $application->run(['check', '--format=human', '--ansi']));
            self::assertStringContainsString('Guard check', $output);
            self::assertStringContainsString("\033[", $output);
            $output = '';
            self::assertSame(0, $application->run(['check', '--format=human', '--no-ansi']));
            self::assertStringNotContainsString("\033", $output);
        } finally {
            putenv($before === false ? 'AI_AGENT' : 'AI_AGENT=' . $before);
            $project->remove();
        }
    }

    public function testRunBaselinesEveryPolicyAndDetectsWorseningMetrics(): void
    {
        $project = new class (['src/A.php' => "<?php\n// first\n// second\n// third\n", 'app.json' => '{"workers":0}', 'guard.yaml' => "version: 1\nmetrics:\n  source: [src]\n  profiles:\n    small:\n      limits:\n        file: {lines: 2}\n  default: small\nstructure:\n  paths: [src]\n  directories:\n    - {path: src, require: [README.md]}\ndocumentation:\n  files:\n    README.md: {headings: ['# Project']}\nconfiguration:\n  - {id: workers, file: app.json, select: /workers, assert: {equals: 2}}\n"]) {
            public string $root;
            /**
             * @param array<array-key, string> $files
             */
            public function __construct(array $files = [])
            {
                $this->root = sys_get_temp_dir() . '/guard-contract-' . uniqid('', true);
                mkdir($this->root);
                foreach ($files as $path => $source) {
                    $this->write((string) $path, $source);
                }
            }
            public function write(string $path, string $source): void
            {
                $directory = dirname($this->root . '/' . $path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($this->root . '/' . $path, $source);
            }


            public function remove(): void
            {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file instanceof SplFileInfo) {
                        if ($file->isDir() && !$file->isLink()) {
                            rmdir($file->getPathname());
                        } else {
                            unlink($file->getPathname());
                        }
                    }
                }
                rmdir($this->root);
            }
        };
        $output = '';
        $app = new \Guard\Cli\Application($project->root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        try {
            self::assertSame(0, $app->run(['baseline', '--format=json']));
            self::assertStringContainsString('"count": 4', $output);
            $snapshot = (string) file_get_contents($project->root . '/guard-baseline.json');
            self::assertStringContainsString('metrics.file_lines', $snapshot);
            self::assertStringContainsString('structure.missing_required_file', $snapshot);
            self::assertStringContainsString('documentation.missing_document', $snapshot);
            self::assertStringContainsString('workers', $snapshot);
            self::assertSame('{"workers":0}', file_get_contents($project->root . '/app.json'));
            $output = '';
            self::assertSame(0, $app->run(['check', '--format=json']));
            self::assertStringContainsString('"suppressed": 4', $output);
            self::assertStringContainsString('"success": true', $output);
            self::assertSame(1, $app->run(['check', '--no-baseline', '--format=ai']));
            $project->write('src/A.php', "<?php\n// first\n// second\n// third\n// fourth\n");
            $output = '';
            self::assertSame(1, $app->run(['check', '--format=json']));
            self::assertStringContainsString('"rule": "metrics.file_lines"', $output);
            self::assertStringContainsString('"suppressed": 3', $output);
            self::assertStringContainsString('"unmatched": 1', $output);
            self::assertSame(0, $app->run(['baseline', '--format=ai']));
            self::assertSame(0, $app->run(['check', '--format=text']));
            self::assertStringContainsString('4 suppressed, 0 unmatched', $output);
        } finally {
            $project->remove();
        }
    }

    public function testRunFixRepairsBaselinedViolationsAndCheckReportsUnmatchedEntries(): void
    {
        $project = new class (['app.json' => '{"workers":0}', 'guard.yaml' => "version: 1\nconfiguration:\n  - {id: workers, file: app.json, select: /workers, assert: {equals: 2}}\n"]) {
            public string $root;
            /**
             * @param array<array-key, string> $files
             */
            public function __construct(array $files = [])
            {
                $this->root = sys_get_temp_dir() . '/guard-contract-' . uniqid('', true);
                mkdir($this->root);
                foreach ($files as $path => $source) {
                    $this->write((string) $path, $source);
                }
            }
            public function write(string $path, string $source): void
            {
                $directory = dirname($this->root . '/' . $path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($this->root . '/' . $path, $source);
            }


            public function remove(): void
            {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file instanceof SplFileInfo) {
                        if ($file->isDir() && !$file->isLink()) {
                            rmdir($file->getPathname());
                        } else {
                            unlink($file->getPathname());
                        }
                    }
                }
                rmdir($this->root);
            }
        };
        $output = '';
        $app = new \Guard\Cli\Application($project->root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        try {
            self::assertSame(0, $app->run(['baseline', '--format=json']));
            self::assertSame(0, $app->run(['fix', '--format=json']));
            self::assertStringContainsString('"workers": 2', (string) file_get_contents($project->root . '/app.json'));
            $output = '';
            self::assertSame(0, $app->run(['check', '--format=json']));
            self::assertStringContainsString('"unmatched": 1', $output);
            self::assertStringContainsString('"suppressed": 0', $output);
        } finally {
            $project->remove();
        }
    }

    public function testRunRejectsMissingAndMalformedBaselinesUnlessDisabled(): void
    {
        $project = new class (['config/team.yaml' => "version: 1\n", 'config/guard-baseline.json' => '{']) {
            public string $root;
            /**
             * @param array<array-key, string> $files
             */
            public function __construct(array $files = [])
            {
                $this->root = sys_get_temp_dir() . '/guard-contract-' . uniqid('', true);
                mkdir($this->root);
                foreach ($files as $path => $source) {
                    $this->write((string) $path, $source);
                }
            }
            public function write(string $path, string $source): void
            {
                $directory = dirname($this->root . '/' . $path);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($this->root . '/' . $path, $source);
            }


            public function remove(): void
            {
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file instanceof SplFileInfo) {
                        if ($file->isDir() && !$file->isLink()) {
                            rmdir($file->getPathname());
                        } else {
                            unlink($file->getPathname());
                        }
                    }
                }
                rmdir($this->root);
            }
        };
        $output = '';
        $app = new \Guard\Cli\Application($project->root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        try {
            self::assertSame(2, $app->run(['check', '-c', 'config/team.yaml']));
            self::assertSame(0, $app->run(['check', '-c', 'config/team.yaml', '--no-baseline']));
            self::assertSame(2, $app->run(['check', '-c', 'config/team.yaml', '--baseline=missing.json']));
            self::assertSame(0, $app->run(['baseline', '-c', 'config/team.yaml', '--output=custom.json']));
            self::assertFileExists($project->root . '/config/custom.json');
            self::assertSame(0, $app->run(['check', '-c', 'config/team.yaml', '--baseline=custom.json']));
            self::assertSame(2, $app->run(['check', '--baseline=custom.json', '--no-baseline']));
            self::assertSame('{', file_get_contents($project->root . '/config/guard-baseline.json'));
        } finally {
            $project->remove();
        }
    }
}
