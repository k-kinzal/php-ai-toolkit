<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Cli\Application
 * @uses \Guard\Cli\ClosureOutput
 * @uses \Guard\Cli\Command\ApplyCommand
 * @uses \Guard\Cli\Command\CheckCommand
 * @uses \Guard\Cli\Command\GuardCommand
 * @uses \Guard\Cli\Command\InitCommand
 * @uses \Guard\Cli\GuardConsole
 * @uses \Guard\Cli\PolicyRun
 * @uses \Guard\Collect\Collector
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\DirectoryTraversal
 * @uses \Guard\Collect\Filesystem\Discovery
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Filesystem\NativeFilesystem
 * @uses \Guard\Collect\Filesystem\Path
 * @uses \Guard\Collect\Filesystem\PatternRoots
 * @uses \Guard\Collect\Filesystem\QueryResult
 * @uses \Guard\Collect\Filesystem\Route
 * @uses \Guard\Collect\Filesystem\SelectionRoots
 * @uses \Guard\Collect\Filesystem\Snapshot
 * @uses \Guard\Collect\Filesystem\WalkQueue
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Matching\GlobMatcher
 * @uses \Guard\Collect\Matching\PathPatternMatcher
 * @uses \Guard\Collect\Matching\SelectionFilter
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Assignment\ApplyRuleMatcher
 * @uses \Guard\Config\Assignment\FilePolicyAssigner
 * @uses \Guard\Config\Assignment\FilePolicyAssignment
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\ConfigurationLoader
 * @uses \Guard\Config\DocumentMerger
 * @uses \Guard\Config\ImportResolver
 * @uses \Guard\Config\Profile\ApplyConfig
 * @uses \Guard\Config\Profile\ApplyConfigReader
 * @uses \Guard\Config\Profile\ApplyPolicyUsageValidator
 * @uses \Guard\Config\Profile\ApplyRuleConfig
 * @uses \Guard\Config\Profile\ApplyRuleConfigReader
 * @uses \Guard\Config\Profile\ApplyRuleListConfigReader
 * @uses \Guard\Config\Profile\PolicyConfig
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
 * @uses \Guard\Config\Value\DeclaredHeading
 * @uses \Guard\Config\Value\DirectoryRuleConfig
 * @uses \Guard\Config\Value\DocumentConfig
 * @uses \Guard\Config\Value\DocumentationConfig
 * @uses \Guard\Config\Value\LimitConfig
 * @uses \Guard\Config\Value\MetricsConfig
 * @uses \Guard\Config\Value\ScanConfig
 * @uses \Guard\Config\Value\StructureConfig
 * @uses \Guard\Document\DataDocument
 * @uses \Guard\Document\DocumentFailure
 * @uses \Guard\Document\DocumentNode
 * @uses \Guard\Document\Json5Reader
 * @uses \Guard\Document\PhpConfigReader
 * @uses \Guard\Document\PhpDocument
 * @uses \Guard\Document\Pointer
 * @uses \Guard\Document\Selection
 * @uses \Guard\Document\TomlEncoder
 * @uses \Guard\Document\XmlDocument
 * @uses \Guard\Execution\AtomicWriter
 * @uses \Guard\Execution\ChangeSet
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Pipeline
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Execution\TargetPath
 * @uses \Guard\Extension\BuiltinExtension
 * @uses \Guard\Extension\ExtensionLoader
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Extension\Registry
 * @uses \Guard\Init\Initializer
 * @uses \Guard\Init\LegacyMigration
 * @uses \Guard\Init\Legacy\DirectoryConfiguration
 * @uses \Guard\Init\Legacy\DirectoryReportConfig
 * @uses \Guard\Init\Legacy\DirectoryReportReader
 * @uses \Guard\Init\Legacy\HeadingConfiguration
 * @uses \Guard\Init\Legacy\HeadingReportConfig
 * @uses \Guard\Init\Legacy\HeadingReportReader
 * @uses \Guard\Init\Legacy\MetricConfiguration
 * @uses \Guard\Init\Legacy\MetricReportConfig
 * @uses \Guard\Init\Legacy\MetricReportReader
 * @uses \Guard\Init\PresetCatalog
 * @uses \Guard\Init\PresetOverrides
 * @uses \Guard\Init\PresetSelector
 * @uses \Guard\Init\Recommendations
 * @uses \Guard\Init\ToolDetector
 * @uses \Guard\Policy\Comparison\HeadingHunkClassifier
 * @uses \Guard\Policy\Comparison\HeadingSequenceAligner
 * @uses \Guard\Policy\Comparison\HeadingStructureComparator
 * @uses \Guard\Policy\Constraint
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
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Policy\RuleEvaluator
 * @uses \Guard\Reporting\DirectoryViolation
 * @uses \Guard\Reporting\Finding
 * @uses \Guard\Reporting\HeadingViolation
 * @uses \Guard\Reporting\HeadingViolationFactory
 * @uses \Guard\Reporting\MetricViolation
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
 */
#[CoversClass(\Guard\Cli\Application::class)]
#[UsesClass(\Guard\Cli\ClosureOutput::class)]
#[UsesClass(\Guard\Cli\Command\ApplyCommand::class)]
#[UsesClass(\Guard\Cli\Command\CheckCommand::class)]
#[UsesClass(\Guard\Cli\Command\GuardCommand::class)]
#[UsesClass(\Guard\Cli\Command\InitCommand::class)]
#[UsesClass(\Guard\Cli\GuardConsole::class)]
#[UsesClass(\Guard\Cli\PolicyRun::class)]
#[UsesClass(\Guard\Collect\Collector::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\DirectoryTraversal::class)]
#[UsesClass(\Guard\Collect\Filesystem\Discovery::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Filesystem\NativeFilesystem::class)]
#[UsesClass(\Guard\Collect\Filesystem\Path::class)]
#[UsesClass(\Guard\Collect\Filesystem\PatternRoots::class)]
#[UsesClass(\Guard\Collect\Filesystem\QueryResult::class)]
#[UsesClass(\Guard\Collect\Filesystem\Route::class)]
#[UsesClass(\Guard\Collect\Filesystem\SelectionRoots::class)]
#[UsesClass(\Guard\Collect\Filesystem\Snapshot::class)]
#[UsesClass(\Guard\Collect\Filesystem\WalkQueue::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Matching\GlobMatcher::class)]
#[UsesClass(\Guard\Collect\Matching\PathPatternMatcher::class)]
#[UsesClass(\Guard\Collect\Matching\SelectionFilter::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Assignment\ApplyRuleMatcher::class)]
#[UsesClass(\Guard\Config\Assignment\FilePolicyAssigner::class)]
#[UsesClass(\Guard\Config\Assignment\FilePolicyAssignment::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\ConfigurationLoader::class)]
#[UsesClass(\Guard\Config\DocumentMerger::class)]
#[UsesClass(\Guard\Config\ImportResolver::class)]
#[UsesClass(\Guard\Config\Profile\ApplyConfig::class)]
#[UsesClass(\Guard\Config\Profile\ApplyConfigReader::class)]
#[UsesClass(\Guard\Config\Profile\ApplyPolicyUsageValidator::class)]
#[UsesClass(\Guard\Config\Profile\ApplyRuleConfig::class)]
#[UsesClass(\Guard\Config\Profile\ApplyRuleConfigReader::class)]
#[UsesClass(\Guard\Config\Profile\ApplyRuleListConfigReader::class)]
#[UsesClass(\Guard\Config\Profile\PolicyConfig::class)]
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
#[UsesClass(\Guard\Config\Value\DeclaredHeading::class)]
#[UsesClass(\Guard\Config\Value\DirectoryRuleConfig::class)]
#[UsesClass(\Guard\Config\Value\DocumentConfig::class)]
#[UsesClass(\Guard\Config\Value\DocumentationConfig::class)]
#[UsesClass(\Guard\Config\Value\LimitConfig::class)]
#[UsesClass(\Guard\Config\Value\MetricsConfig::class)]
#[UsesClass(\Guard\Config\Value\ScanConfig::class)]
#[UsesClass(\Guard\Config\Value\StructureConfig::class)]
#[UsesClass(\Guard\Document\DataDocument::class)]
#[UsesClass(\Guard\Document\DocumentFailure::class)]
#[UsesClass(\Guard\Document\DocumentNode::class)]
#[UsesClass(\Guard\Document\Json5Reader::class)]
#[UsesClass(\Guard\Document\PhpConfigReader::class)]
#[UsesClass(\Guard\Document\PhpDocument::class)]
#[UsesClass(\Guard\Document\Pointer::class)]
#[UsesClass(\Guard\Document\Selection::class)]
#[UsesClass(\Guard\Document\TomlEncoder::class)]
#[UsesClass(\Guard\Document\XmlDocument::class)]
#[UsesClass(\Guard\Execution\AtomicWriter::class)]
#[UsesClass(\Guard\Execution\ChangeSet::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Pipeline::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Execution\TargetPath::class)]
#[UsesClass(\Guard\Extension\BuiltinExtension::class)]
#[UsesClass(\Guard\Extension\ExtensionLoader::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(\Guard\Extension\Registry::class)]
#[UsesClass(\Guard\Init\Initializer::class)]
#[UsesClass(\Guard\Init\LegacyMigration::class)]
#[UsesClass(\Guard\Init\Legacy\DirectoryConfiguration::class)]
#[UsesClass(\Guard\Init\Legacy\DirectoryReportConfig::class)]
#[UsesClass(\Guard\Init\Legacy\DirectoryReportReader::class)]
#[UsesClass(\Guard\Init\Legacy\HeadingConfiguration::class)]
#[UsesClass(\Guard\Init\Legacy\HeadingReportConfig::class)]
#[UsesClass(\Guard\Init\Legacy\HeadingReportReader::class)]
#[UsesClass(\Guard\Init\Legacy\MetricConfiguration::class)]
#[UsesClass(\Guard\Init\Legacy\MetricReportConfig::class)]
#[UsesClass(\Guard\Init\Legacy\MetricReportReader::class)]
#[UsesClass(\Guard\Init\PresetCatalog::class)]
#[UsesClass(\Guard\Init\PresetOverrides::class)]
#[UsesClass(\Guard\Init\PresetSelector::class)]
#[UsesClass(\Guard\Init\Recommendations::class)]
#[UsesClass(\Guard\Init\ToolDetector::class)]
#[UsesClass(\Guard\Policy\Comparison\HeadingHunkClassifier::class)]
#[UsesClass(\Guard\Policy\Comparison\HeadingSequenceAligner::class)]
#[UsesClass(\Guard\Policy\Comparison\HeadingStructureComparator::class)]
#[UsesClass(\Guard\Policy\Constraint::class)]
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
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Policy\RuleEvaluator::class)]
#[UsesClass(\Guard\Reporting\DirectoryViolation::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
#[UsesClass(\Guard\Reporting\HeadingViolation::class)]
#[UsesClass(\Guard\Reporting\HeadingViolationFactory::class)]
#[UsesClass(\Guard\Reporting\MetricViolation::class)]
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
final class ApplicationTest extends TestCase
{
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
        self::assertSame(0, $app->run(['apply', '--dry-run']));
        self::assertSame('{"workers":0,"other":"keep"}', file_get_contents($root . '/app.json'));
        self::assertSame(0, $app->run(['apply']));
        self::assertSame(['workers' => 1, 'other' => 'keep'], json_decode((string) file_get_contents($root . '/app.json'), true, 512, JSON_THROW_ON_ERROR));
        $after = file_get_contents($root . '/app.json');
        self::assertSame(0, $app->run(['apply']));
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
        self::assertSame(0, $app->run(['apply']));
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
        self::assertSame(1, $app->run(['apply']));
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
        self::assertMatchesRegularExpression('/^  apply +Repair /m', $output);
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
        self::assertStringContainsString('Presets: metrics, structure', $output);
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
        self::assertSame(2, $app->run(['apply', '--bogus']));
        self::assertSame("Guard error: The \"--bogus\" option does not exist.\nRun \"guard apply --help\" to see its options.\n", $output);
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
        self::assertTrue($console->has('apply'));
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
        self::assertSame(2, $app->run(['apply']));
        self::assertSame('{"mode":"B"}', file_get_contents($root . '/a.json'));
        self::assertSame('{', file_get_contents($root . '/b.json'));
        self::assertStringContainsString('Guard error:', $output);
        self::assertStringContainsString('b.json', $output);
    }
}
