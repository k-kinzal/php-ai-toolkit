<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Filesystem;

use FilesystemIterator;
use Guard\Collect\Collector;
use Guard\Collect\Filesystem\DirectoryTraversal;
use Guard\Collect\Filesystem\Discovery;
use Guard\Collect\Filesystem\Filesystem;
use Guard\Collect\Filesystem\NativeFilesystem;
use Guard\Collect\Filesystem\PatternRoots;
use Guard\Collect\Filesystem\QueryResult;
use Guard\Collect\Filesystem\Route;
use Guard\Collect\Filesystem\ScopedRoots;
use Guard\Collect\Filesystem\SelectionRoots;
use Guard\Collect\Filesystem\Snapshot;
use Guard\Collect\Filesystem\TargetPath;
use Guard\Collect\Filesystem\WalkQueue;
use Guard\Collect\Matching\GlobMatcher;
use Guard\Collect\Matching\ScopeMatcher;
use Guard\Collect\Matching\SelectionFilter;
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
use Guard\Config\Reader\ScopeConfigReader;
use Guard\Config\RuleReader;
use Guard\Config\Schema;
use Guard\Config\Validation\DirectoryConfigScalarReader;
use Guard\Config\Validation\DirectoryConfigStringListReader;
use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Config\Validation\MetricConfigKeyValidator;
use Guard\Config\Validation\MetricConfigScalarReader;
use Guard\Config\Validation\MetricConfigStringListReader;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\DirectoryListing;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Path;
use Guard\Input\PathPatternMatcher;
use Guard\Input\Scope;
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
use Guard\Structure\Structurer;
use Guard\Structure\Subject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @covers \Guard\Collect\Filesystem\ScopedRoots
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
 * @uses \Guard\Collect\Matching\ScopeMatcher
 * @uses \Guard\Collect\Matching\SelectionFilter
 * @uses \Guard\Input\Scope
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
 * @uses \Guard\Config\Reader\ScopeConfigReader
 * @uses \Guard\Config\RuleReader
 * @uses \Guard\Config\Schema
 * @uses \Guard\Config\Validation\DirectoryConfigScalarReader
 * @uses \Guard\Config\Validation\DirectoryConfigStringListReader
 * @uses \Guard\Config\Validation\HeadingConfigKeyValidator
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
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Collect\Filesystem\TargetPath
 * @uses \Guard\Policy\PolicyBinding
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
 * @uses \Guard\Policy\Diagnostic\FieldMessage
 */
#[CoversClass(ScopedRoots::class)]
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
#[UsesClass(ScopeMatcher::class)]
#[UsesClass(SelectionFilter::class)]
#[UsesClass(Scope::class)]
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
#[UsesClass(ScopeConfigReader::class)]
#[UsesClass(RuleReader::class)]
#[UsesClass(Schema::class)]
#[UsesClass(DirectoryConfigScalarReader::class)]
#[UsesClass(DirectoryConfigStringListReader::class)]
#[UsesClass(HeadingConfigKeyValidator::class)]
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
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(TargetPath::class)]
#[UsesClass(PolicyBinding::class)]
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
#[UsesClass(FieldMessage::class)]
final class ScopedRootsTest extends TestCase
{
    public function testEntryRejectsLiteralSymlinkParentsEvenWhenTheirTargetsAreIncluded(): void
    {
        $project = new class (['src/actual/data.xml' => '<a/>']) {
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
        try {
            symlink($project->root . '/src/actual', $project->root . '/src/alias');
            $filesystem = new class () implements Filesystem {
                /** @var array<string, int> */
                public array $listings = [];
                /** @var array<string, int> */
                public array $reads = [];
                /** @var array<string, int> */
                public array $inspections = [];
                public function inspect(string $path): Entry
                {
                    $this->inspections[$path] = ($this->inspections[$path] ?? 0) + 1;
                    return (new NativeFilesystem())->inspect($path);
                }
                public function entries(string $path): array|false
                {
                    $key = realpath($path);
                    $key = $key === false ? $path : $key;
                    $this->listings[$key] = ($this->listings[$key] ?? 0) + 1;
                    return (new NativeFilesystem())->entries($path);
                }
                public function read(string $path): string|false
                {
                    $key = realpath($path);
                    $key = $key === false ? $path : $key;
                    $this->reads[$key] = ($this->reads[$key] ?? 0) + 1;
                    return (new NativeFilesystem())->read($path);
                }
            };
            $sets = (new Collector($filesystem))->collect($project->root, [
                'exact' => new Input(new Selection('files', ['src/alias/data.xml']), 'xml'),
                'pattern' => new Input(new Selection('patterns', ['src/alias/*.xml']), 'xml'),
            ], ['xml' => new DocumentStructurer('xml')], new Scope(['src']));
            self::assertSame([], $sets['exact']->files);
            self::assertSame([], $sets['pattern']->files);
            self::assertArrayNotHasKey($project->root . '/src/alias/data.xml', $filesystem->inspections);
            self::assertSame([], $filesystem->reads);
            self::assertSame([], $filesystem->listings);
        } finally {
            $project->remove();
        }
    }
    public function testSeedIntersectsPhpMarkdownAndXmlDemandsBeforeTheSharedTraversal(): void
    {
        $project = new class (['src/A.php' => '<?php echo 1;', 'src/generated/B.php' => 'ignored', 'docs/A.md' => '# A', 'README.md' => '# Root', 'assets/app.xml' => '<app/>', 'assets/unread.txt' => 'unused', 'vendor/X.xml' => '<broken', 'outside/Z.php' => 'unused']) {
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
        try {
            $filesystem = new class () implements Filesystem {
                /** @var array<string, int> */
                public array $listings = [];
                /** @var array<string, int> */
                public array $reads = [];
                /** @var array<string, int> */
                public array $inspections = [];
                public function inspect(string $path): Entry
                {
                    $this->inspections[$path] = ($this->inspections[$path] ?? 0) + 1;
                    return (new NativeFilesystem())->inspect($path);
                }
                public function entries(string $path): array|false
                {
                    $key = realpath($path);
                    $key = $key === false ? $path : $key;
                    $this->listings[$key] = ($this->listings[$key] ?? 0) + 1;
                    return (new NativeFilesystem())->entries($path);
                }
                public function read(string $path): string|false
                {
                    $key = realpath($path);
                    $key = $key === false ? $path : $key;
                    $this->reads[$key] = ($this->reads[$key] ?? 0) + 1;
                    return (new NativeFilesystem())->read($path);
                }
            };
            $xml = new class (new DocumentStructurer('xml')) implements Structurer {
                public int $calls = 0;
                public function __construct(private Structurer $inner)
                {
                }
                public function structure(Source $source): Subject
                {
                    $this->calls++;
                    return $this->inner->structure($source);
                }
            };
            $sets = (new Collector($filesystem))->collect($project->root, [
                'php' => new Input(new Selection('patterns', ['**/*.php']), 'php.metrics'),
                'md' => new Input(new Selection('patterns', ['README.md', 'docs/**/*.md']), 'markdown.headings'),
                'xml' => new Input(new Selection('patterns', ['**/*.xml']), 'xml'),
                'sameXml' => new Input(new Selection('files', ['assets/app.xml']), 'xml'),
            ], ['php.tokens' => new TokenParser(), 'php.metrics' => new MetricParser(), 'markdown.headings' => new HeadingStructurer(), 'xml' => $xml], new Scope(['src', 'src/**', './docs', 'README.md', 'assets'], ['src/generated']));
            self::assertSame(['src/A.php'], array_keys($sets['php']->files));
            self::assertSame(['README.md', 'docs/A.md'], array_keys($sets['md']->files));
            self::assertSame(['assets/app.xml'], array_keys($sets['xml']->files));
            self::assertSame($sets['xml']->files['assets/app.xml']->data, $sets['sameXml']->files['assets/app.xml']->data);
            self::assertSame(1, $xml->calls);
            self::assertCount(3, $filesystem->listings);
            self::assertSame([1], array_values(array_unique($filesystem->listings)));
            self::assertCount(4, $filesystem->reads);
            self::assertSame([1], array_values(array_unique($filesystem->reads)));
            self::assertArrayNotHasKey($project->root . '/vendor', $filesystem->inspections);
            self::assertArrayNotHasKey($project->root . '/src/generated', $filesystem->inspections);
        } finally {
            $project->remove();
        }
    }

    public function testStartUsesBothLiteralPrefixesAndPrunesImpossibleGlobDescendants(): void
    {
        $project = new class (['src/lib/Only.php' => '<?php', 'src/lib/deep/Only.php' => 'unused', 'unrelated/X.php' => 'unused']) {
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
        try {
            $filesystem = new class () implements Filesystem {
                /** @var array<string, int> */
                public array $listings = [];
                /** @var array<string, int> */
                public array $reads = [];
                /** @var array<string, int> */
                public array $inspections = [];
                public function inspect(string $path): Entry
                {
                    $this->inspections[$path] = ($this->inspections[$path] ?? 0) + 1;
                    return (new NativeFilesystem())->inspect($path);
                }
                public function entries(string $path): array|false
                {
                    $key = realpath($path);
                    $key = $key === false ? $path : $key;
                    $this->listings[$key] = ($this->listings[$key] ?? 0) + 1;
                    return (new NativeFilesystem())->entries($path);
                }
                public function read(string $path): string|false
                {
                    $key = realpath($path);
                    $key = $key === false ? $path : $key;
                    $this->reads[$key] = ($this->reads[$key] ?? 0) + 1;
                    return (new NativeFilesystem())->read($path);
                }
            };
            $sets = (new Collector($filesystem))->collect($project->root, ['files' => new Input(new Selection('patterns', ['src/lib/**/*.php']))], [], new Scope(['src/*/Only.php']));
            self::assertSame(['src/lib/Only.php'], array_keys($sets['files']->files));
            self::assertSame([realpath($project->root) . '/src/lib' => 1], $filesystem->listings);
            self::assertArrayNotHasKey($project->root . '/src/lib/deep', $filesystem->inspections);
            self::assertSame([], $filesystem->reads);
        } finally {
            $project->remove();
        }
    }

    public function testExactCannotWidenTheBoundaryOrReadExcludedMalformedDocuments(): void
    {
        $project = new class (['src/keep.json' => '{}', 'src/skip.json' => '{', 'outside.json' => '{']) {
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
        try {
            $filesystem = new class () implements Filesystem {
                /** @var array<string, int> */
                public array $listings = [];
                /** @var array<string, int> */
                public array $reads = [];
                /** @var array<string, int> */
                public array $inspections = [];
                public function inspect(string $path): Entry
                {
                    $this->inspections[$path] = ($this->inspections[$path] ?? 0) + 1;
                    return (new NativeFilesystem())->inspect($path);
                }
                public function entries(string $path): array|false
                {
                    $key = realpath($path);
                    $key = $key === false ? $path : $key;
                    $this->listings[$key] = ($this->listings[$key] ?? 0) + 1;
                    return (new NativeFilesystem())->entries($path);
                }
                public function read(string $path): string|false
                {
                    $key = realpath($path);
                    $key = $key === false ? $path : $key;
                    $this->reads[$key] = ($this->reads[$key] ?? 0) + 1;
                    return (new NativeFilesystem())->read($path);
                }
            };
            $sets = (new Collector($filesystem))->collect($project->root, ['files' => new Input(new Selection('files', ['./src/keep.json', 'src/skip.json', '/outside/file.json', 'outside.json']), 'json')], ['json' => new DocumentStructurer('json')], new Scope(['src'], ['src/skip.json']));
            self::assertSame(['./src/keep.json'], array_keys($sets['files']->files));
            self::assertCount(1, $filesystem->reads);
            self::assertSame([], $filesystem->listings);
            self::assertArrayNotHasKey('/outside/file.json', $filesystem->inspections);
            self::assertArrayNotHasKey($project->root . '/src/skip.json', $filesystem->inspections);
        } finally {
            $project->remove();
        }
    }

    public function testPositionsReplaysPrefixesWithoutEnumeratingParents(): void
    {
        $filesystem = new class () implements Filesystem {
            /** @var array<string, int> */
            public array $listings = [];
            /** @var array<string, int> */
            public array $reads = [];
            /** @var array<string, int> */
            public array $inspections = [];
            public function inspect(string $path): Entry
            {
                $this->inspections[$path] = ($this->inspections[$path] ?? 0) + 1;
                return (new NativeFilesystem())->inspect($path);
            }
            public function entries(string $path): array|false
            {
                $key = realpath($path);
                $key = $key === false ? $path : $key;
                $this->listings[$key] = ($this->listings[$key] ?? 0) + 1;
                return (new NativeFilesystem())->entries($path);
            }
            public function read(string $path): string|false
            {
                $key = realpath($path);
                $key = $key === false ? $path : $key;
                $this->reads[$key] = ($this->reads[$key] ?? 0) + 1;
                return (new NativeFilesystem())->read($path);
            }
        };
        $roots = new ScopedRoots(new Snapshot($filesystem), new ScopeMatcher('/root', '/root', new Scope()));
        self::assertSame([0], $roots->positions(['**', '*.xml'], 'assets/nested', true));
        self::assertSame([2], $roots->positions(['**', '*.xml'], 'assets/file.xml', false));
        self::assertSame([], $roots->positions(['docs', '*.md'], 'assets', true));
        self::assertSame([], $filesystem->inspections);
    }

    public function testSeedKeepsMissingExactFilesAndEmptyScopesDistinct(): void
    {
        $project = new class () {
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
        try {
            $filesystem = new class () implements Filesystem {
                /** @var array<string, int> */
                public array $listings = [];
                /** @var array<string, int> */
                public array $reads = [];
                /** @var array<string, int> */
                public array $inspections = [];
                public function inspect(string $path): Entry
                {
                    $this->inspections[$path] = ($this->inspections[$path] ?? 0) + 1;
                    return (new NativeFilesystem())->inspect($path);
                }
                public function entries(string $path): array|false
                {
                    $key = realpath($path);
                    $key = $key === false ? $path : $key;
                    $this->listings[$key] = ($this->listings[$key] ?? 0) + 1;
                    return (new NativeFilesystem())->entries($path);
                }
                public function read(string $path): string|false
                {
                    $key = realpath($path);
                    $key = $key === false ? $path : $key;
                    $this->reads[$key] = ($this->reads[$key] ?? 0) + 1;
                    return (new NativeFilesystem())->read($path);
                }
            };
            $collector = new Collector($filesystem);
            $inputs = ['file' => new Input(new Selection('files', ['README.md']))];
            $missing = $collector->collect($project->root, $inputs, [], new Scope(['README.md']));
            self::assertFalse($missing['file']->files['README.md']->file->entry->file);
            self::assertSame([], $collector->collect($project->root, $inputs, [], new Scope([]))['file']->files);
            self::assertSame([], $filesystem->listings);
            self::assertSame([], $filesystem->reads);
        } finally {
            $project->remove();
        }
    }

    public function testStartConstrainsDirectoryListingsAndRecursiveSuffixRequests(): void
    {
        $project = new class (['src/A.php' => '<?php', 'src/B.txt' => 'text', 'src/excluded/X.php' => 'unused', 'vendor/X.php' => 'unused']) {
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
        try {
            $filesystem = new class () implements Filesystem {
                /** @var array<string, int> */
                public array $listings = [];
                /** @var array<string, int> */
                public array $reads = [];
                /** @var array<string, int> */
                public array $inspections = [];
                public function inspect(string $path): Entry
                {
                    $this->inspections[$path] = ($this->inspections[$path] ?? 0) + 1;
                    return (new NativeFilesystem())->inspect($path);
                }
                public function entries(string $path): array|false
                {
                    $key = realpath($path);
                    $key = $key === false ? $path : $key;
                    $this->listings[$key] = ($this->listings[$key] ?? 0) + 1;
                    return (new NativeFilesystem())->entries($path);
                }
                public function read(string $path): string|false
                {
                    $key = realpath($path);
                    $key = $key === false ? $path : $key;
                    $this->reads[$key] = ($this->reads[$key] ?? 0) + 1;
                    return (new NativeFilesystem())->read($path);
                }
            };
            $sets = (new Collector($filesystem))->collect($project->root, [
                'dirs' => new Input(new Selection('directories', ['.'])),
                'php' => new Input(new Selection('descendants', ['.'], [], '.php')),
            ], [], new Scope(['src'], ['src/excluded']));
            self::assertSame(['src'], array_keys($sets['dirs']->directories));
            self::assertSame(['A.php', 'B.txt'], $sets['dirs']->directories['src']->fileNames);
            self::assertSame([], $sets['dirs']->directories['src']->dirNames);
            self::assertSame(['src/A.php'], array_keys($sets['php']->files));
            self::assertCount(1, $filesystem->listings);
            self::assertSame([], $filesystem->reads);
        } finally {
            $project->remove();
        }
    }

    public function testStartDoesNotReadThroughSymlinksOrLiteralAliasesOutsideTheScope(): void
    {
        $project = new class (['src/A.xml' => '<a/>', 'vendor/B.xml' => '<broken']) {
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
        try {
            symlink($project->root . '/vendor', $project->root . '/src/alias');
            $filesystem = new class () implements Filesystem {
                /** @var array<string, int> */
                public array $listings = [];
                /** @var array<string, int> */
                public array $reads = [];
                /** @var array<string, int> */
                public array $inspections = [];
                public function inspect(string $path): Entry
                {
                    $this->inspections[$path] = ($this->inspections[$path] ?? 0) + 1;
                    return (new NativeFilesystem())->inspect($path);
                }
                public function entries(string $path): array|false
                {
                    $key = realpath($path);
                    $key = $key === false ? $path : $key;
                    $this->listings[$key] = ($this->listings[$key] ?? 0) + 1;
                    return (new NativeFilesystem())->entries($path);
                }
                public function read(string $path): string|false
                {
                    $key = realpath($path);
                    $key = $key === false ? $path : $key;
                    $this->reads[$key] = ($this->reads[$key] ?? 0) + 1;
                    return (new NativeFilesystem())->read($path);
                }
            };
            $sets = (new Collector($filesystem))->collect($project->root, ['files' => new Input(new Selection('patterns', ['src/**/*.xml', 'src/alias/B.xml']), 'xml')], ['xml' => new DocumentStructurer('xml')], new Scope(['src']));
            self::assertSame(['src/A.xml'], array_keys($sets['files']->files));
            self::assertCount(1, $filesystem->reads);
            self::assertArrayNotHasKey(realpath($project->root) . '/vendor', $filesystem->listings);
        } finally {
            $project->remove();
        }
    }
}
