<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Project\Initializer
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
 * @uses \Guard\Repair\ChangeSet
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Execution\Pipeline
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Collect\Filesystem\TargetPath
 * @uses \Guard\Execution\Registry
 * @uses \Guard\Config\ComponentLoader
 * @uses \Guard\Policy\PolicyBinding
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
#[CoversClass(\Guard\Config\Project\Initializer::class)]
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
#[UsesClass(\Guard\Repair\ChangeSet::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Execution\Pipeline::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Collect\Filesystem\TargetPath::class)]
#[UsesClass(\Guard\Execution\Registry::class)]
#[UsesClass(\Guard\Config\ComponentLoader::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
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
#[UsesClass(\Guard\Policy\Diagnostic\FieldMessage::class)]
final class InitializerTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testWriteCreatesRecommendedPolicyWithoutOverwritingExistingFiles(): void
    {
        $root = sys_get_temp_dir() . '/guard-init-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/composer.json', '{"config":{"sort-packages":false}}');
        (new \Guard\Config\Project\Initializer())->write($root . '/guard.yaml');
        $config = (new \Guard\Config\ConfigurationLoader())->load($root . '/guard.yaml');
        $plan = (new \Guard\Execution\Pipeline())->run($config, $root . '/guard.yaml', false);
        self::assertSame('recommended', $plan->findings[0]->level);
        self::assertSame('{"config":{"sort-packages":false}}', file_get_contents($root . '/composer.json'));
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        (new \Guard\Config\Project\Initializer())->write($root . '/guard.yaml');
    }

    /**
     * @throws JsonException

     */
    public function testConfigurationAvoidsAbsentTools(): void
    {
        $root = sys_get_temp_dir() . '/guard-empty-' . uniqid();
        mkdir($root);
        $config = (new \Guard\Config\Project\Initializer())->configuration($root);
        self::assertArrayNotHasKey('configuration', $config);
        self::assertSame([(new \Guard\Config\Project\PresetCatalog())->importPath($root, 'disable-doc')], $config['imports']);
        self::assertArrayNotHasKey('metrics', $config);
        self::assertArrayNotHasKey('scope', $config);
    }

    /**
     * @throws JsonException

     */
    public function testConfigurationImportsOnlyRequestedPresets(): void
    {
        $root = sys_get_temp_dir() . '/guard-requested-' . uniqid();
        mkdir($root);
        $config = (new \Guard\Config\Project\Initializer())->configuration($root, ['composer', 'composer']);
        self::assertStringContainsString('composer.yaml', json_encode($config['imports'], JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('metrics.yaml', json_encode($config['imports'], JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('disable-doc.yaml', json_encode($config['imports'], JSON_THROW_ON_ERROR));
    }

    /**
     * @throws JsonException

     */
    public function testConfigurationKeepsLegacyMetricsOutOfImports(): void
    {
        $root = sys_get_temp_dir() . '/guard-legacy-init-' . uniqid();
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/loc.yaml', "scan: {roots: [lib], exclude: []}\npolicies:\n  strict:\n    limits: {file: {lines: 99}}\napply: {default: strict, rules: []}\n");
        $config = (new \Guard\Config\Project\Initializer())->configuration($root, ['metrics', 'structure']);
        self::assertStringNotContainsString('metrics.yaml', json_encode($config['imports'], JSON_THROW_ON_ERROR));
        self::assertSame(['source' => ['lib'], 'exclude' => [], 'profiles' => ['strict' => ['limits' => ['file' => ['lines' => 99]]]], 'default' => 'strict', 'assignments' => []], $config['metrics']);
    }

    /**
     * @throws JsonException

     */
    public function testConfigurationOverridesDetectedPhpStanFile(): void
    {
        $root = sys_get_temp_dir() . '/guard-phpstan-init-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/composer.json', '{"require-dev":{"phpstan/phpstan":"^2.0","k-kinzal/phpstan-guard-rules":"^1.0"}}');
        file_put_contents($root . '/phpstan.neon.dist', "parameters:\n    level: 5\n");
        $config = (new \Guard\Config\Project\Initializer())->configuration($root);
        self::assertSame([
            ['id' => 'phpstan.level', 'file' => 'phpstan.neon.dist'],
            ['id' => 'phpstan.strict-rules', 'file' => 'phpstan.neon.dist'],
            ['id' => 'phpstan.rules', 'file' => 'phpstan.neon.dist'],
            ['id' => 'phpstan.extension', 'file' => 'phpstan.neon.dist'],
            ['id' => 'phpstan.all-rules', 'file' => 'phpstan.neon.dist'],
        ], $config['configuration']);
    }

    /**

     */
    public function testReadmeRecordsTheCurrentHeadings(): void
    {
        $root = sys_get_temp_dir() . '/guard-readme-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/README.md', "# Tool\n");
        self::assertSame(['files' => ['README.md' => ['headings' => ['# Tool']]], 'scan' => ['README.md']], (new \Guard\Config\Project\Initializer())->readme($root));
        self::assertNull((new \Guard\Config\Project\Initializer())->readme(sys_get_temp_dir() . '/guard-no-readme-' . uniqid()));
    }
    /**

     */
    public function testLimitsPreservesTheCurrentStrictProfile(): void
    {
        $limits = (new \Guard\Config\Project\Initializer())->limits();
        self::assertSame(['lines' => 500, 'ncloc' => 350], $limits['file']);
        self::assertSame(['lines' => 50, 'cyclomatic_complexity' => 20], $limits['method']);
    }
    /**
     * @throws JsonException

     */
    public function testStructureRetainsNamingAndDensityConstraints(): void
    {
        $structure = (new \Guard\Config\Project\Initializer())->structure(['src']);
        self::assertSame(['.'], $structure['paths']);
        self::assertStringContainsString('*Helper.php', json_encode($structure, JSON_THROW_ON_ERROR));
        self::assertStringContainsString('"max_files":15', json_encode($structure, JSON_THROW_ON_ERROR));
    }
}
