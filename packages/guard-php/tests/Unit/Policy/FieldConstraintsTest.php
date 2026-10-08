<?php

declare(strict_types=1);

namespace Tests\Unit\Policy;

use FilesystemIterator;
use Guard\Config\Configuration;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Context;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * @covers \Guard\Policy\FieldConstraints
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
 * @uses \Guard\Execution\Registry
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Policy\Comparison\HeadingHunkClassifier
 * @uses \Guard\Policy\Comparison\HeadingSequenceAligner
 * @uses \Guard\Policy\Comparison\HeadingStructureComparator
 * @uses \Guard\Structure\Document\Constraint
 * @uses \Guard\Policy\DirectoryEntries
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
#[CoversClass(\Guard\Policy\FieldConstraints::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Collector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\DirectoryTraversal::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Discovery::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\NativeFilesystem::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Path::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\PatternRoots::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\QueryResult::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Route::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\SelectionRoots::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Snapshot::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\WalkQueue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Matching\GlobMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\PathPatternMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Matching\SelectionFilter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Assignment\ApplyRuleMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Assignment\FilePolicyAssigner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Assignment\FilePolicyAssignment::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\ConfigurationLoader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\DocumentMerger::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\ImportResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\ApplyConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\ApplyConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\ApplyPolicyUsageValidator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\ApplyRuleConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\ApplyRuleConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\ApplyRuleListConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\PolicyConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\PolicyConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\PolicyDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\PolicyListConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Profile\PolicyResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\DeclaredHeadingReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\DirectoryPolicyReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\DirectoryRuleConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\DirectoryRuleListConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\DocumentConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\DocumentListConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\ExtensionConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\HeadingPolicyReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\LimitConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Reader\MetricPolicyReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\RuleReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Validation\DirectoryConfigScalarReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Validation\DirectoryConfigStringListReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Validation\HeadingConfigKeyValidator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Validation\MetricConfigKeyValidator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Validation\MetricConfigScalarReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Validation\MetricConfigStringListReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\DeclaredHeading::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\DirectoryRuleConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\DocumentConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\DocumentationConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\LimitConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\MetricsConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\ScanConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Definition\StructureConfig::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\DataDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\DocumentFailure::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\DocumentNode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\Json5Reader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\PhpConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\PhpDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\Pointer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\TomlEncoder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\XmlDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\TargetPath::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Registry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Comparison\HeadingHunkClassifier::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Comparison\HeadingSequenceAligner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Comparison\HeadingStructureComparator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Document\Constraint::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\DirectoryEntries::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\HeadingStructure::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Inspection\CaseConventionMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Inspection\ChildCountInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Inspection\DepthInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Inspection\DirNameInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Inspection\DirectoryPatternMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Inspection\DirectoryRuleInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Inspection\EmptyDirectoryInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Inspection\FileNameInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Inspection\RequiredFileInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Inspection\TotalFileCountInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\ClassLikeMetricLimit::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\ClassLikeMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FileMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FunctionComplexityViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FunctionLineViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\FunctionMetricViolationBuilder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Limit\MetricLimitInspector::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\MetricLimits::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\RuleEvaluator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Diagnostic\DirectoryViolation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\Finding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Diagnostic\HeadingViolation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Diagnostic\HeadingViolationFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Diagnostic\MetricViolation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\DocumentStructurer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\AtxHeadingMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\BlockMarkerMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\Block\BlockLineScanner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\Fence::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\FenceMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\Heading::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\HeadingList::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\HeadingParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\HeadingStructurer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\HeadingTextNormalizer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\HtmlBlockMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\LineIndentation::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\LineScanner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\MarkdownLineSplitter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\ParserState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\SetextUnderlineMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\ParsedDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\ClassLikeMetric\ClassLikeMetricParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Complexity\CyclomaticComplexityCalculator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Complexity\CyclomaticComplexityState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Complexity\CyclomaticDecisionWeight::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FileMetric\FileMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionBodyLocator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionLineParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionMetric::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionMetricComplexityAssigner::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionMetricParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionNameReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionScanState::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\FunctionMetric\NestedFunctionMetricRange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\MetricParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\SourceMetrics::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\TokenParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Token\ClassLikeTokenMatcher::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Token\CodeTokenLineResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Token\PhpTokenNavigator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Token\TokenLineCounter::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Tokens::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Source::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Diagnostic\FieldMessage::class)]
final class FieldConstraintsTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testEvaluateConflictsBlockWritesAfterAllRulesAreRechecked(): void
    {
        $project = new class (['app.json' => '{"mode":"C"}', 'guard.yaml' => "version: 1\nconfiguration:\n  - {id: a, file: app.json, select: /mode, assert: {equals: A}}\n  - {id: b, file: app.json, select: /mode, assert: {equals: B}}\n"]) {
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
            /**
             * @throws JsonException
             */
            public function context(bool $repair = false): Context
            {
                $path = $this->root . '/guard.yaml';
                $config = is_file($path) ? (new \Guard\Config\ConfigurationLoader())->load($path) : new Configuration($this->root, []);
                return new Context($config->root, $path, $repair, $config->scope);
            }
            /**
             * @return array<array-key, string>
             * @throws RuntimeException
             */
            public function files(): array
            {
                $files = [];
                foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS)) as $file) {
                    if ($file instanceof SplFileInfo && $file->isFile()) {
                        $source = file_get_contents($file->getPathname());
                        if ($source === false) {
                            throw new RuntimeException('Cannot read test fixture ' . $file->getPathname());
                        }
                        $files[substr($file->getPathname(), strlen($this->root) + 1)] = $source;
                    }
                }
                ksort($files, SORT_STRING);
                return $files;
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
            $context = $project->context(true);
            $registry = \Guard\Execution\Registry::defaults((new \Guard\Config\ConfigurationLoader())->load($context->configPath)->policies);
            $bindings = array_values(array_filter($registry->policies(), static fn (\Guard\Policy\PolicyBinding $binding): bool => $binding->id === 'field-constraints'));
            self::assertCount(1, $bindings);
            $policy = $bindings[0]->policy;
            $subject = new \Guard\Input\InputSet((new \Guard\Collect\Collector())->collect($project->root, $policy->inputs($context), $registry->structures()));
            $subject->validate();
            $plan = $policy->evaluate($subject, $context);
            self::assertCount(1, $plan->findings);
            self::assertSame('a', $plan->findings[0]->rule);
            self::assertSame($plan->findings, $plan->blockingFindings);
            self::assertSame('{"mode":"C"}', $project->files()['app.json']);
            $original = $subject->get('file0')->files['app.json']->value();
            self::assertInstanceOf(\Guard\Structure\ParsedDocument::class, $original);
            self::assertSame('C', $original->copy()->read('/mode')->value);
        } finally {
            $project->remove();
        }
    }
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testPlanAlreadyCompliantDocumentIsByteIdentical(): void
    {
        $project = new class (['app.yaml' => "# Keep this comment\nmode: A\n", 'guard.yaml' => "version: 1\nconfiguration:\n  - {id: a, file: app.yaml, select: /mode, assert: {one_of: [A, B]}, repair: B}\n"]) {
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
            /**
             * @throws JsonException
             */
            public function context(bool $repair = false): Context
            {
                $path = $this->root . '/guard.yaml';
                $config = is_file($path) ? (new \Guard\Config\ConfigurationLoader())->load($path) : new Configuration($this->root, []);
                return new Context($config->root, $path, $repair, $config->scope);
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
            $context = $project->context(true);
            $registry = \Guard\Execution\Registry::defaults((new \Guard\Config\ConfigurationLoader())->load($context->configPath)->policies);
            $bindings = array_values(array_filter($registry->policies(), static fn (\Guard\Policy\PolicyBinding $binding): bool => $binding->id === 'field-constraints'));
            self::assertCount(1, $bindings);
            $policy = $bindings[0]->policy;
            $subject = new \Guard\Input\InputSet((new \Guard\Collect\Collector())->collect($project->root, $policy->inputs($context), $registry->structures()));
            $subject->validate();
            $plan = $policy->evaluate($subject, $context);
            self::assertSame([], $plan->findings);
            self::assertSame([], $plan->changes);
        } finally {
            $project->remove();
        }
    }
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testEvaluateXmlRepairsDoNotMutateTheSnapshotOrReadTheFileAgain(): void
    {
        $project = new class (['app.xml' => '<app value="0"/>', 'guard.yaml' => "version: 1\nconfiguration:\n  - {id: a, file: app.xml, select: /app/@value, assert: {equals: '1'}}\n"]) {
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
            /**
             * @throws JsonException
             */
            public function context(bool $repair = false): Context
            {
                $path = $this->root . '/guard.yaml';
                $config = is_file($path) ? (new \Guard\Config\ConfigurationLoader())->load($path) : new Configuration($this->root, []);
                return new Context($config->root, $path, $repair, $config->scope);
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
            $context = $project->context(true);
            $registry = \Guard\Execution\Registry::defaults((new \Guard\Config\ConfigurationLoader())->load($context->configPath)->policies);
            $bindings = array_values(array_filter($registry->policies(), static fn (\Guard\Policy\PolicyBinding $binding): bool => $binding->id === 'field-constraints'));
            self::assertCount(1, $bindings);
            $policy = $bindings[0]->policy;
            $subject = new \Guard\Input\InputSet((new \Guard\Collect\Collector())->collect($project->root, $policy->inputs($context), $registry->structures()));
            $subject->validate();
            unlink($project->root . '/app.xml');

            $first = $policy->evaluate($subject, $context);
            $second = $policy->evaluate($subject, $context);
            $original = $subject->get('file0')->files['app.xml']->value();
            self::assertInstanceOf(\Guard\Structure\ParsedDocument::class, $original);
            self::assertSame('0', $original->copy()->read('/app/@value')->value);
            self::assertSame($first->changes[0]->replacement, $second->changes[0]->replacement);
            self::assertSame([], $first->findings);
        } finally {
            $project->remove();
        }
    }
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */


    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testEvaluateReportsEachRuleOfAMissingTargetAtItsLevel(): void
    {
        $project = new class (['guard.yaml' => "version: 1\nconfiguration:\n  - {id: a, file: app.json, select: /mode, assert: {equals: A}}\n  - {id: b, file: app.json, select: /count, level: recommended, assert: {min: 1}, repair: 1}\n"]) {
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
            /**
             * @throws JsonException
             */
            public function context(bool $repair = false): Context
            {
                $path = $this->root . '/guard.yaml';
                $config = is_file($path) ? (new \Guard\Config\ConfigurationLoader())->load($path) : new Configuration($this->root, []);
                return new Context($config->root, $path, $repair, $config->scope);
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
            $context = $project->context(true);
            $registry = \Guard\Execution\Registry::defaults((new \Guard\Config\ConfigurationLoader())->load($context->configPath)->policies);
            $bindings = array_values(array_filter($registry->policies(), static fn (\Guard\Policy\PolicyBinding $binding): bool => $binding->id === 'field-constraints'));
            self::assertCount(1, $bindings);
            $policy = $bindings[0]->policy;
            $subject = new \Guard\Input\InputSet((new \Guard\Collect\Collector())->collect($project->root, $policy->inputs($context), $registry->structures()));
            $subject->validate();
            $plan = $policy->evaluate($subject, $context);
            self::assertSame(['a', 'b'], array_map(static fn (\Guard\Diagnostic\Finding $finding): string => $finding->rule, $plan->findings));
            self::assertSame(['required', 'recommended'], array_map(static fn (\Guard\Diagnostic\Finding $finding): string => $finding->level, $plan->findings));
            self::assertSame('app.json does not exist. Create it with the required configuration: /mode in app.json must equal "A". Set /mode in app.json to "A". Removing the rule requires a human to update guard.yaml.', $plan->findings[0]->message);
            self::assertSame([], $plan->changes);
            self::assertSame($plan->findings, $plan->blockingFindings);
        } finally {
            $project->remove();
        }
    }

    /**
     * @throws JsonException
     */
    public function testMissingNamesThePolicyFile(): void
    {
        $rules = (new \Guard\Config\RuleReader())->read([['id' => 'x', 'file' => 'app.json', 'select' => '/x', 'assert' => ['present' => true]]]);

        $findings = (new \Guard\Policy\FieldConstraints($rules))->missing($rules, 'custom.yaml');

        self::assertCount(1, $findings);
        self::assertSame('app.json', $findings[0]->path);
        self::assertFalse($findings[0]->fixable);
        self::assertStringContainsString('Add the missing field /x in app.json.', $findings[0]->message);
        self::assertStringNotContainsString('Run guard fix', $findings[0]->message);
        self::assertStringEndsWith('Removing the rule requires a human to update custom.yaml.', $findings[0]->message);
    }

    /**
     * @throws JsonException

     */
    public function testInputsDeclaresTheRequiredStructureWithoutFilesystemAccess(): void
    {
        $policy = new \Guard\Policy\FieldConstraints((new \Guard\Config\RuleReader())->read([['id' => 'x', 'file' => 'app.xml.dist', 'format' => 'xml', 'select' => '/app/@x', 'assert' => ['equals' => '1']]]));
        $context = new Context('/none', '/none/guard.yaml', false);
        $inputs = $policy->inputs($context);
        self::assertSame('xml', $inputs['file0']->structure);
    }
}
