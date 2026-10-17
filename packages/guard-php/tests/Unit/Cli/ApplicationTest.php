<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use FilesystemIterator;
use Guard\Cli\Application;
use Guard\Cli\BaselineFile;
use Guard\Cli\CheckRun;
use Guard\Cli\ClosureOutput;
use Guard\Cli\Command\BaselineCommand;
use Guard\Cli\Command\CheckCommand;
use Guard\Cli\Command\FixCommand;
use Guard\Cli\Command\GuardCommand;
use Guard\Cli\Command\InitCommand;
use Guard\Cli\Command\RulesCommand;
use Guard\Cli\FormatDetector;
use Guard\Cli\GuardConsole;
use Guard\Cli\PolicyRun;
use Guard\Collect\Collector;
use Guard\Collect\Filesystem\DirectoryTraversal;
use Guard\Collect\Filesystem\Discovery;
use Guard\Collect\Filesystem\NativeFilesystem;
use Guard\Collect\Filesystem\PatternRoots;
use Guard\Collect\Filesystem\QueryResult;
use Guard\Collect\Filesystem\Route;
use Guard\Collect\Filesystem\SelectionRoots;
use Guard\Collect\Filesystem\Snapshot;
use Guard\Collect\Filesystem\TargetPath;
use Guard\Collect\Filesystem\WalkQueue;
use Guard\Collect\Matching\GlobMatcher;
use Guard\Collect\Matching\SelectionFilter;
use Guard\Config\ComponentLoader;
use Guard\Config\Configuration;
use Guard\Config\ConfigurationLoader;
use Guard\Config\DocumentMerger;
use Guard\Config\ImportResolver;
use Guard\Config\Profile\ApplyConfigReader;
use Guard\Config\Profile\ApplyPolicyUsageValidator;
use Guard\Config\Profile\ApplyRuleConfigReader;
use Guard\Config\Profile\ApplyRuleListConfigReader;
use Guard\Config\Profile\PolicyConfigReader;
use Guard\Config\Profile\PolicyDefinition;
use Guard\Config\Profile\PolicyListConfigReader;
use Guard\Config\Profile\PolicyResolver;
use Guard\Config\Project\Initializer;
use Guard\Config\Project\Legacy\DirectoryConfiguration;
use Guard\Config\Project\Legacy\DirectoryReportConfig;
use Guard\Config\Project\Legacy\DirectoryReportReader;
use Guard\Config\Project\Legacy\HeadingConfiguration;
use Guard\Config\Project\Legacy\HeadingReportConfig;
use Guard\Config\Project\Legacy\HeadingReportReader;
use Guard\Config\Project\Legacy\MetricConfiguration;
use Guard\Config\Project\Legacy\MetricReportConfig;
use Guard\Config\Project\Legacy\MetricReportReader;
use Guard\Config\Project\LegacyMigration;
use Guard\Config\Project\PresetCatalog;
use Guard\Config\Project\PresetOverrides;
use Guard\Config\Project\PresetSelector;
use Guard\Config\Project\Recommendations;
use Guard\Config\Project\ToolDetector;
use Guard\Config\Reader\DeclaredHeadingReader;
use Guard\Config\Reader\DirectoryPolicyReader;
use Guard\Config\Reader\DirectoryRuleConfigReader;
use Guard\Config\Reader\DirectoryRuleListConfigReader;
use Guard\Config\Reader\DocumentConfigReader;
use Guard\Config\Reader\DocumentListConfigReader;
use Guard\Config\Reader\ExtensionConfigReader;
use Guard\Config\Reader\HeadingPolicyReader;
use Guard\Config\Reader\LimitConfigReader;
use Guard\Config\Reader\MetricPolicyReader;
use Guard\Config\Reader\ScanConfigReader;
use Guard\Config\RuleReader;
use Guard\Config\Schema;
use Guard\Config\Validation\DirectoryConfigScalarReader;
use Guard\Config\Validation\DirectoryConfigStringListReader;
use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Config\Validation\HeadingConfigStringListReader;
use Guard\Config\Validation\MetricConfigKeyValidator;
use Guard\Config\Validation\MetricConfigScalarReader;
use Guard\Config\Validation\MetricConfigStringListReader;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Execution\Pipeline;
use Guard\Execution\Registry;
use Guard\Input\DirectoryListing;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Path;
use Guard\Input\PathPatternMatcher;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Assignment\ApplyRuleMatcher;
use Guard\Policy\Assignment\FilePolicyAssigner;
use Guard\Policy\Assignment\FilePolicyAssignment;
use Guard\Policy\Comparison\HeadingHunkClassifier;
use Guard\Policy\Comparison\HeadingSequenceAligner;
use Guard\Policy\Comparison\HeadingStructureComparator;
use Guard\Policy\Context;
use Guard\Policy\Definition\ApplyConfig;
use Guard\Policy\Definition\ApplyRuleConfig;
use Guard\Policy\Definition\DeclaredHeading;
use Guard\Policy\Definition\DirectoryRuleConfig;
use Guard\Policy\Definition\DocumentationConfig;
use Guard\Policy\Definition\DocumentConfig;
use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Definition\MetricsConfig;
use Guard\Policy\Definition\PolicyConfig;
use Guard\Policy\Definition\ScanConfig;
use Guard\Policy\Definition\StructureConfig;
use Guard\Policy\Diagnostic\DirectoryViolation;
use Guard\Policy\Diagnostic\FieldMessage;
use Guard\Policy\Diagnostic\HeadingViolation;
use Guard\Policy\Diagnostic\HeadingViolationFactory;
use Guard\Policy\Diagnostic\MetricViolation;
use Guard\Policy\Diagnostic\RuleMessages;
use Guard\Policy\DirectoryEntries;
use Guard\Policy\FieldConstraints;
use Guard\Policy\FileChange;
use Guard\Policy\HeadingStructure;
use Guard\Policy\Inspection\CaseConventionMatcher;
use Guard\Policy\Inspection\ChildCountInspector;
use Guard\Policy\Inspection\DepthInspector;
use Guard\Policy\Inspection\DirectoryPatternMatcher;
use Guard\Policy\Inspection\DirectoryRuleInspector;
use Guard\Policy\Inspection\DirNameInspector;
use Guard\Policy\Inspection\EmptyDirectoryInspector;
use Guard\Policy\Inspection\FileNameInspector;
use Guard\Policy\Inspection\RequiredFileInspector;
use Guard\Policy\Inspection\TotalFileCountInspector;
use Guard\Policy\Limit\ClassLikeMetricLimit;
use Guard\Policy\Limit\ClassLikeMetricViolationBuilder;
use Guard\Policy\Limit\FileMetricViolationBuilder;
use Guard\Policy\Limit\FunctionComplexityViolationBuilder;
use Guard\Policy\Limit\FunctionLineViolationBuilder;
use Guard\Policy\Limit\FunctionMetricViolationBuilder;
use Guard\Policy\Limit\MetricLimitInspector;
use Guard\Policy\MetricLimits;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use Guard\Policy\Rule;
use Guard\Policy\RuleEvaluator;
use Guard\Repair\AtomicWriter;
use Guard\Repair\ChangeSet;
use Guard\Reporting\Baseline;
use Guard\Reporting\BaselineMatch;
use Guard\Reporting\ChangeDiff;
use Guard\Reporting\Filtering\FindingFilter;
use Guard\Reporting\Filtering\ReportScope;
use Guard\Reporting\Reporter;
use Guard\Structure\Document\Constraint;
use Guard\Structure\Document\DataDocument;
use Guard\Structure\Document\DocumentFailure;
use Guard\Structure\Document\DocumentNode;
use Guard\Structure\Document\Json5Reader;
use Guard\Structure\Document\PhpConfigReader;
use Guard\Structure\Document\PhpDocument;
use Guard\Structure\Document\Pointer;
use Guard\Structure\Document\TomlEncoder;
use Guard\Structure\Document\XmlDocument;
use Guard\Structure\DocumentStructurer;
use Guard\Structure\Markdown\AtxHeadingMatcher;
use Guard\Structure\Markdown\Block\BlockLineScanner;
use Guard\Structure\Markdown\BlockMarkerMatcher;
use Guard\Structure\Markdown\Fence;
use Guard\Structure\Markdown\FenceMatcher;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingList;
use Guard\Structure\Markdown\HeadingParser;
use Guard\Structure\Markdown\HeadingStructurer;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use Guard\Structure\Markdown\HtmlBlockMatcher;
use Guard\Structure\Markdown\LineIndentation;
use Guard\Structure\Markdown\LineScanner;
use Guard\Structure\Markdown\MarkdownLineSplitter;
use Guard\Structure\Markdown\ParserState;
use Guard\Structure\Markdown\SetextUnderlineMatcher;
use Guard\Structure\ParsedDocument;
use Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader;
use Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric;
use Guard\Structure\Php\ClassLikeMetric\ClassLikeMetricParser;
use Guard\Structure\Php\Complexity\CyclomaticComplexityCalculator;
use Guard\Structure\Php\Complexity\CyclomaticComplexityState;
use Guard\Structure\Php\Complexity\CyclomaticDecisionWeight;
use Guard\Structure\Php\FileMetric\FileMetric;
use Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary;
use Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader;
use Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader;
use Guard\Structure\Php\FunctionMetric\FunctionBodyLocator;
use Guard\Structure\Php\FunctionMetric\FunctionLineParser;
use Guard\Structure\Php\FunctionMetric\FunctionMetric;
use Guard\Structure\Php\FunctionMetric\FunctionMetricComplexityAssigner;
use Guard\Structure\Php\FunctionMetric\FunctionMetricParser;
use Guard\Structure\Php\FunctionMetric\FunctionNameReader;
use Guard\Structure\Php\FunctionMetric\FunctionScanState;
use Guard\Structure\Php\FunctionMetric\NestedFunctionMetricRange;
use Guard\Structure\Php\MetricParser;
use Guard\Structure\Php\SourceMetrics;
use Guard\Structure\Php\Token\ClassLikeTokenMatcher;
use Guard\Structure\Php\Token\CodeTokenLineResolver;
use Guard\Structure\Php\Token\PhpTokenNavigator;
use Guard\Structure\Php\Token\TokenLineCounter;
use Guard\Structure\Php\TokenParser;
use Guard\Structure\Php\Tokens;
use Guard\Structure\Source;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Output\ConsoleOutput;

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
#[CoversClass(Application::class)]
#[UsesClass(ClosureOutput::class)]
#[UsesClass(FixCommand::class)]
#[UsesClass(CheckCommand::class)]
#[UsesClass(GuardCommand::class)]
#[UsesClass(InitCommand::class)]
#[UsesClass(GuardConsole::class)]
#[UsesClass(PolicyRun::class)]
#[UsesClass(Collector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(DirectoryTraversal::class)]
#[UsesClass(Discovery::class)]
#[UsesClass(Entry::class)]
#[UsesClass(NativeFilesystem::class)]
#[UsesClass(Path::class)]
#[UsesClass(PatternRoots::class)]
#[UsesClass(QueryResult::class)]
#[UsesClass(Route::class)]
#[UsesClass(SelectionRoots::class)]
#[UsesClass(Snapshot::class)]
#[UsesClass(WalkQueue::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(GlobMatcher::class)]
#[UsesClass(PathPatternMatcher::class)]
#[UsesClass(SelectionFilter::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(ApplyRuleMatcher::class)]
#[UsesClass(FilePolicyAssigner::class)]
#[UsesClass(FilePolicyAssignment::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationLoader::class)]
#[UsesClass(DocumentMerger::class)]
#[UsesClass(ImportResolver::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(ApplyConfigReader::class)]
#[UsesClass(ApplyPolicyUsageValidator::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(ApplyRuleConfigReader::class)]
#[UsesClass(ApplyRuleListConfigReader::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(PolicyConfigReader::class)]
#[UsesClass(PolicyDefinition::class)]
#[UsesClass(PolicyListConfigReader::class)]
#[UsesClass(PolicyResolver::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(DirectoryPolicyReader::class)]
#[UsesClass(DirectoryRuleConfigReader::class)]
#[UsesClass(DirectoryRuleListConfigReader::class)]
#[UsesClass(DocumentConfigReader::class)]
#[UsesClass(DocumentListConfigReader::class)]
#[UsesClass(ExtensionConfigReader::class)]
#[UsesClass(HeadingPolicyReader::class)]
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(MetricPolicyReader::class)]
#[UsesClass(ScanConfigReader::class)]
#[UsesClass(RuleReader::class)]
#[UsesClass(Schema::class)]
#[UsesClass(DirectoryConfigScalarReader::class)]
#[UsesClass(DirectoryConfigStringListReader::class)]
#[UsesClass(HeadingConfigKeyValidator::class)]
#[UsesClass(HeadingConfigStringListReader::class)]
#[UsesClass(MetricConfigKeyValidator::class)]
#[UsesClass(MetricConfigScalarReader::class)]
#[UsesClass(MetricConfigStringListReader::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DirectoryRuleConfig::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(DocumentationConfig::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(MetricsConfig::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(StructureConfig::class)]
#[UsesClass(DataDocument::class)]
#[UsesClass(DocumentFailure::class)]
#[UsesClass(DocumentNode::class)]
#[UsesClass(Json5Reader::class)]
#[UsesClass(PhpConfigReader::class)]
#[UsesClass(PhpDocument::class)]
#[UsesClass(Pointer::class)]
#[UsesClass(\Guard\Structure\Document\Selection::class)]
#[UsesClass(TomlEncoder::class)]
#[UsesClass(XmlDocument::class)]
#[UsesClass(AtomicWriter::class)]
#[UsesClass(ChangeSet::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Pipeline::class)]
#[UsesClass(Plan::class)]
#[UsesClass(TargetPath::class)]
#[UsesClass(Registry::class)]
#[UsesClass(ComponentLoader::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(Initializer::class)]
#[UsesClass(LegacyMigration::class)]
#[UsesClass(DirectoryConfiguration::class)]
#[UsesClass(DirectoryReportConfig::class)]
#[UsesClass(DirectoryReportReader::class)]
#[UsesClass(HeadingConfiguration::class)]
#[UsesClass(HeadingReportConfig::class)]
#[UsesClass(HeadingReportReader::class)]
#[UsesClass(MetricConfiguration::class)]
#[UsesClass(MetricReportConfig::class)]
#[UsesClass(MetricReportReader::class)]
#[UsesClass(PresetCatalog::class)]
#[UsesClass(PresetOverrides::class)]
#[UsesClass(PresetSelector::class)]
#[UsesClass(Recommendations::class)]
#[UsesClass(ToolDetector::class)]
#[UsesClass(HeadingHunkClassifier::class)]
#[UsesClass(HeadingSequenceAligner::class)]
#[UsesClass(HeadingStructureComparator::class)]
#[UsesClass(Constraint::class)]
#[UsesClass(DirectoryEntries::class)]
#[UsesClass(FieldConstraints::class)]
#[UsesClass(HeadingStructure::class)]
#[UsesClass(CaseConventionMatcher::class)]
#[UsesClass(ChildCountInspector::class)]
#[UsesClass(DepthInspector::class)]
#[UsesClass(DirNameInspector::class)]
#[UsesClass(DirectoryPatternMatcher::class)]
#[UsesClass(DirectoryRuleInspector::class)]
#[UsesClass(EmptyDirectoryInspector::class)]
#[UsesClass(FileNameInspector::class)]
#[UsesClass(RequiredFileInspector::class)]
#[UsesClass(TotalFileCountInspector::class)]
#[UsesClass(ClassLikeMetricLimit::class)]
#[UsesClass(ClassLikeMetricViolationBuilder::class)]
#[UsesClass(FileMetricViolationBuilder::class)]
#[UsesClass(FunctionComplexityViolationBuilder::class)]
#[UsesClass(FunctionLineViolationBuilder::class)]
#[UsesClass(FunctionMetricViolationBuilder::class)]
#[UsesClass(MetricLimitInspector::class)]
#[UsesClass(MetricLimits::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Rule::class)]
#[UsesClass(RuleEvaluator::class)]
#[UsesClass(DirectoryViolation::class)]
#[UsesClass(Finding::class)]
#[UsesClass(HeadingViolation::class)]
#[UsesClass(HeadingViolationFactory::class)]
#[UsesClass(MetricViolation::class)]
#[UsesClass(Reporter::class)]
#[UsesClass(DocumentStructurer::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(BlockMarkerMatcher::class)]
#[UsesClass(BlockLineScanner::class)]
#[UsesClass(Fence::class)]
#[UsesClass(FenceMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingList::class)]
#[UsesClass(HeadingParser::class)]
#[UsesClass(HeadingStructurer::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(HtmlBlockMatcher::class)]
#[UsesClass(LineIndentation::class)]
#[UsesClass(LineScanner::class)]
#[UsesClass(MarkdownLineSplitter::class)]
#[UsesClass(ParserState::class)]
#[UsesClass(SetextUnderlineMatcher::class)]
#[UsesClass(ParsedDocument::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(ClassLikeMetric::class)]
#[UsesClass(ClassLikeMetricParser::class)]
#[UsesClass(CyclomaticComplexityCalculator::class)]
#[UsesClass(CyclomaticComplexityState::class)]
#[UsesClass(CyclomaticDecisionWeight::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(ArrowExpressionBoundary::class)]
#[UsesClass(ArrowFunctionMetricReader::class)]
#[UsesClass(BlockFunctionMetricReader::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionLineParser::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionMetricComplexityAssigner::class)]
#[UsesClass(FunctionMetricParser::class)]
#[UsesClass(FunctionNameReader::class)]
#[UsesClass(FunctionScanState::class)]
#[UsesClass(NestedFunctionMetricRange::class)]
#[UsesClass(MetricParser::class)]
#[UsesClass(SourceMetrics::class)]
#[UsesClass(TokenParser::class)]
#[UsesClass(ClassLikeTokenMatcher::class)]
#[UsesClass(CodeTokenLineResolver::class)]
#[UsesClass(PhpTokenNavigator::class)]
#[UsesClass(TokenLineCounter::class)]
#[UsesClass(Tokens::class)]
#[UsesClass(Source::class)]
#[UsesClass(ChangeDiff::class)]
#[UsesClass(RuleMessages::class)]
#[UsesClass(FormatDetector::class)]
#[UsesClass(RulesCommand::class)]
#[UsesClass(FieldMessage::class)]
#[UsesClass(CheckRun::class)]
#[UsesClass(BaselineFile::class)]
#[UsesClass(BaselineCommand::class)]
#[UsesClass(Baseline::class)]
#[UsesClass(BaselineMatch::class)]
#[UsesClass(FindingFilter::class)]
#[UsesClass(ReportScope::class)]
final class ApplicationTest extends TestCase
{
    public function testInitRejectsNewDocsIncludingEmptyDirectories(): void
    {
        $root = sys_get_temp_dir() . '/guard-disable-doc-' . uniqid();
        mkdir($root . '/vendor/example/docs', 0777, true);
        mkdir($root . '/packages/example/docs', 0777, true);
        $output = '';
        $app = new Application($root, static function (string $text) use (&$output): void {
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
        $app = new Application($root, static function (string $text) use (&$output): void {
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
        $app = new Application($root, static function (string $text) use (&$output): void {
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
        $app = new Application($root, static function (string $text) use (&$output): void {
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
        $app = new Application($root, static function (string $text) use (&$output): void {
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
        $app = new Application($root, static function (string $text) use (&$output): void {
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
        $app = new Application($root, static function (string $text) use (&$output): void {
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
        $app = new Application(sys_get_temp_dir(), static function (string $text) use (&$output): void {
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
        $app = new Application(sys_get_temp_dir(), static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(0, $app->run(['init', '--help']));
        self::assertStringContainsString('--import=IMPORT', $output);
        self::assertStringNotContainsString('Help:', $output);
    }
    public function testRunReportsTheVersion(): void
    {
        $output = '';
        $app = new Application(sys_get_temp_dir(), static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(0, $app->run(['--version']));
        self::assertSame("guard 1.0.0\n", $output);
    }
    public function testRunRejectsAnUnknownOptionWithAPointerToTheCommandHelp(): void
    {
        $output = '';
        $app = new Application(sys_get_temp_dir(), static function (string $text) use (&$output): void {
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
        $app = new Application($root, static function (string $text) use (&$output): void {
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
        $app = new Application($root, static function (string $text) use (&$output): void {
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
        $console = (new Application(sys_get_temp_dir()))->console();
        self::assertSame('guard', $console->getName());
        self::assertTrue($console->has('check'));
        self::assertTrue($console->has('fix'));
        self::assertTrue($console->has('init'));
    }
    public function testOutputWritesToTheProcessStreamsWithoutAnInjectedSink(): void
    {
        self::assertInstanceOf(ConsoleOutput::class, (new Application(sys_get_temp_dir()))->output());
    }
    public function testOutputWritesToTheInjectedSink(): void
    {
        $output = '';
        (new Application(sys_get_temp_dir(), static function (string $text) use (&$output): void {
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
        $app = new Application($root, static function (string $text) use (&$output): void {
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
        $application = new Application($project->root, static function (string $text) use (&$output): void {
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
        $application = new Application($project->root, static function (string $text) use (&$output): void {
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
        $app = new Application($project->root, static function (string $text) use (&$output): void {
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
        $app = new Application($project->root, static function (string $text) use (&$output): void {
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
        $app = new Application($project->root, static function (string $text) use (&$output): void {
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
