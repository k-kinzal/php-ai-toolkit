<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Cli\Application
 * @uses \Guard\Cli\Arguments
 * @uses \Guard\Collect\Configuration\ConfigurationCollector
 * @uses \Guard\Collect\Configuration\ConfigurationDocument
 * @uses \Guard\Collect\Markdown\Filesystem\MarkdownFileFinder
 * @uses \Guard\Collect\Markdown\Filesystem\MarkdownFileReader
 * @uses \Guard\Collect\Markdown\Filesystem\PathResolver
 * @uses \Guard\Collect\Markdown\MarkdownCollector
 * @uses \Guard\Collect\Markdown\MarkdownDocuments
 * @uses \Guard\Collect\Markdown\Parsing\AtxHeadingMatcher
 * @uses \Guard\Collect\Markdown\Parsing\BlockLineScanner
 * @uses \Guard\Collect\Markdown\Parsing\BlockMarkerMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Fence
 * @uses \Guard\Collect\Markdown\Parsing\FenceMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Heading
 * @uses \Guard\Collect\Markdown\Parsing\HeadingParser
 * @uses \Guard\Collect\Markdown\Parsing\HeadingTextNormalizer
 * @uses \Guard\Collect\Markdown\Parsing\HtmlBlockMatcher
 * @uses \Guard\Collect\Markdown\Parsing\LineIndentation
 * @uses \Guard\Collect\Markdown\Parsing\LineScanner
 * @uses \Guard\Collect\Markdown\Parsing\MarkdownLineSplitter
 * @uses \Guard\Collect\Markdown\Parsing\ParserState
 * @uses \Guard\Collect\Markdown\Parsing\SetextUnderlineMatcher
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeMetricCollector
 * @uses \Guard\Collect\Php\Complexity\CyclomaticComplexityCalculator
 * @uses \Guard\Collect\Php\Complexity\CyclomaticComplexityState
 * @uses \Guard\Collect\Php\Complexity\CyclomaticDecisionWeight
 * @uses \Guard\Collect\Php\FileMetric\FileMetric
 * @uses \Guard\Collect\Php\Filesystem\FilePathPatternMatcher
 * @uses \Guard\Collect\Php\Filesystem\PathResolver
 * @uses \Guard\Collect\Php\Filesystem\PhpFileFinder
 * @uses \Guard\Collect\Php\Filesystem\PhpFileInclusionPolicy
 * @uses \Guard\Collect\Php\Filesystem\PhpPathFileCollector
 * @uses \Guard\Collect\Php\FunctionMetric\ArrowExpressionBoundary
 * @uses \Guard\Collect\Php\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Guard\Collect\Php\FunctionMetric\BlockFunctionMetricReader
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionBodyLocator
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetricCollector
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetricComplexityAssigner
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionMetricLineCollector
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionNameReader
 * @uses \Guard\Collect\Php\FunctionMetric\FunctionScanState
 * @uses \Guard\Collect\Php\FunctionMetric\NestedFunctionMetricRange
 * @uses \Guard\Collect\Php\PhpCollector
 * @uses \Guard\Collect\Php\PhpSources
 * @uses \Guard\Collect\Php\SourceMetricReader
 * @uses \Guard\Collect\Php\SourceMetrics
 * @uses \Guard\Collect\Php\Token\ClassLikeTokenMatcher
 * @uses \Guard\Collect\Php\Token\CodeTokenLineResolver
 * @uses \Guard\Collect\Php\Token\PhpTokenNavigator
 * @uses \Guard\Collect\Php\Token\TokenLineCounter
 * @uses \Guard\Collect\Tree\DirectoryTree
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryListing
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryListingReader
 * @uses \Guard\Collect\Tree\Filesystem\DirectoryTreeScanner
 * @uses \Guard\Collect\Tree\Filesystem\PathInclusionPolicy
 * @uses \Guard\Collect\Tree\Filesystem\PathResolver
 * @uses \Guard\Collect\Tree\TreeCollector
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\ConfigurationLoader
 * @uses \Guard\Config\Doc\ConfigKeyValidator
 * @uses \Guard\Config\Doc\ConfigStringListReader
 * @uses \Guard\Config\Doc\DeclaredHeading
 * @uses \Guard\Config\Doc\DeclaredHeadingReader
 * @uses \Guard\Config\Doc\DocumentConfig
 * @uses \Guard\Config\Doc\DocumentConfigReader
 * @uses \Guard\Config\Doc\DocumentListConfigReader
 * @uses \Guard\Config\Doc\DocumentationConfig
 * @uses \Guard\Config\DocumentMerger
 * @uses \Guard\Config\DocumentationReader
 * @uses \Guard\Config\ImportResolver
 * @uses \Guard\Config\Loc\ConfigKeyValidator
 * @uses \Guard\Config\Loc\ConfigScalarReader
 * @uses \Guard\Config\Loc\ConfigStringListReader
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Config\Loc\LimitConfigReader
 * @uses \Guard\Config\Loc\MetricsConfig
 * @uses \Guard\Config\Loc\Policy\ApplyConfig
 * @uses \Guard\Config\Loc\Policy\ApplyConfigReader
 * @uses \Guard\Config\Loc\Policy\ApplyPolicyUsageValidator
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfig
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfigReader
 * @uses \Guard\Config\Loc\Policy\ApplyRuleListConfigReader
 * @uses \Guard\Config\Loc\Policy\PolicyConfig
 * @uses \Guard\Config\Loc\Policy\PolicyConfigReader
 * @uses \Guard\Config\Loc\Policy\PolicyDefinition
 * @uses \Guard\Config\Loc\Policy\PolicyListConfigReader
 * @uses \Guard\Config\Loc\Policy\PolicyResolver
 * @uses \Guard\Config\Loc\ScanConfig
 * @uses \Guard\Config\Loc\ScanConfigReader
 * @uses \Guard\Config\MetricsReader
 * @uses \Guard\Config\RuleReader
 * @uses \Guard\Config\Schema
 * @uses \Guard\Config\StructureReader
 * @uses \Guard\Config\Tree\ConfigScalarReader
 * @uses \Guard\Config\Tree\ConfigStringListReader
 * @uses \Guard\Config\Tree\RuleConfig
 * @uses \Guard\Config\Tree\RuleConfigReader
 * @uses \Guard\Config\Tree\RuleListConfigReader
 * @uses \Guard\Config\Tree\StructureConfig
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
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Extension\Registry
 * @uses \Guard\Init\Initializer
 * @uses \Guard\Init\LegacyMigration
 * @uses \Guard\Init\Legacy\Doc\ConfigLoader
 * @uses \Guard\Init\Legacy\Doc\ReportConfig
 * @uses \Guard\Init\Legacy\Doc\ReportConfigReader
 * @uses \Guard\Init\Legacy\Loc\ConfigLoader
 * @uses \Guard\Init\Legacy\Loc\ReportConfig
 * @uses \Guard\Init\Legacy\Loc\ReportConfigReader
 * @uses \Guard\Init\Legacy\Tree\ConfigLoader
 * @uses \Guard\Init\Legacy\Tree\ReportConfig
 * @uses \Guard\Init\Legacy\Tree\ReportConfigReader
 * @uses \Guard\Init\PresetCatalog
 * @uses \Guard\Init\PresetOverrides
 * @uses \Guard\Init\PresetSelector
 * @uses \Guard\Init\Recommendations
 * @uses \Guard\Init\ToolDetector
 * @uses \Guard\Policy\Configuration\ConfigurationPolicy
 * @uses \Guard\Policy\Constraint
 * @uses \Guard\Policy\Doc\DocPolicy
 * @uses \Guard\Policy\Doc\HeadingHunkClassifier
 * @uses \Guard\Policy\Doc\HeadingSequenceAligner
 * @uses \Guard\Policy\Doc\HeadingStructureComparator
 * @uses \Guard\Policy\Doc\Violation
 * @uses \Guard\Policy\Doc\ViolationFactory
 * @uses \Guard\Policy\Loc\Assignment\ApplyRuleMatcher
 * @uses \Guard\Policy\Loc\Assignment\FilePolicyAssigner
 * @uses \Guard\Policy\Loc\Assignment\FilePolicyAssignment
 * @uses \Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricLimit
 * @uses \Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricViolationBuilder
 * @uses \Guard\Policy\Loc\FileMetric\FileMetricViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionComplexityViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionLineViolationBuilder
 * @uses \Guard\Policy\Loc\FunctionMetric\FunctionMetricViolationBuilder
 * @uses \Guard\Policy\Loc\LocPolicy
 * @uses \Guard\Policy\Loc\Violation
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Policy\RuleEvaluator
 * @uses \Guard\Policy\Tree\CaseConventionMatcher
 * @uses \Guard\Policy\Tree\ChildCountInspector
 * @uses \Guard\Policy\Tree\DepthInspector
 * @uses \Guard\Policy\Tree\DirNameInspector
 * @uses \Guard\Policy\Tree\DirectoryPatternMatcher
 * @uses \Guard\Policy\Tree\DirectoryRuleInspector
 * @uses \Guard\Policy\Tree\EmptyDirectoryInspector
 * @uses \Guard\Policy\Tree\FileNameInspector
 * @uses \Guard\Policy\Tree\RequiredFileInspector
 * @uses \Guard\Policy\Tree\TotalFileCountInspector
 * @uses \Guard\Policy\Tree\TreePolicy
 * @uses \Guard\Policy\Tree\Violation
 * @uses \Guard\Reporting\Finding
 * @uses \Guard\Reporting\Reporter
 */
#[CoversClass(\Guard\Cli\Application::class)]
#[UsesClass(\Guard\Cli\Arguments::class)]
#[UsesClass(\Guard\Collect\Configuration\ConfigurationCollector::class)]
#[UsesClass(\Guard\Collect\Configuration\ConfigurationDocument::class)]
#[UsesClass(\Guard\Collect\Markdown\Filesystem\MarkdownFileFinder::class)]
#[UsesClass(\Guard\Collect\Markdown\Filesystem\MarkdownFileReader::class)]
#[UsesClass(\Guard\Collect\Markdown\Filesystem\PathResolver::class)]
#[UsesClass(\Guard\Collect\Markdown\MarkdownCollector::class)]
#[UsesClass(\Guard\Collect\Markdown\MarkdownDocuments::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\AtxHeadingMatcher::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\BlockLineScanner::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\BlockMarkerMatcher::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\Fence::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\FenceMatcher::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\Heading::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\HeadingParser::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\HeadingTextNormalizer::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\HtmlBlockMatcher::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\LineIndentation::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\LineScanner::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\MarkdownLineSplitter::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\ParserState::class)]
#[UsesClass(\Guard\Collect\Markdown\Parsing\SetextUnderlineMatcher::class)]
#[UsesClass(\Guard\Collect\Php\ClassLikeMetric\ClassLikeDeclarationReader::class)]
#[UsesClass(\Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric::class)]
#[UsesClass(\Guard\Collect\Php\ClassLikeMetric\ClassLikeMetricCollector::class)]
#[UsesClass(\Guard\Collect\Php\Complexity\CyclomaticComplexityCalculator::class)]
#[UsesClass(\Guard\Collect\Php\Complexity\CyclomaticComplexityState::class)]
#[UsesClass(\Guard\Collect\Php\Complexity\CyclomaticDecisionWeight::class)]
#[UsesClass(\Guard\Collect\Php\FileMetric\FileMetric::class)]
#[UsesClass(\Guard\Collect\Php\Filesystem\FilePathPatternMatcher::class)]
#[UsesClass(\Guard\Collect\Php\Filesystem\PathResolver::class)]
#[UsesClass(\Guard\Collect\Php\Filesystem\PhpFileFinder::class)]
#[UsesClass(\Guard\Collect\Php\Filesystem\PhpFileInclusionPolicy::class)]
#[UsesClass(\Guard\Collect\Php\Filesystem\PhpPathFileCollector::class)]
#[UsesClass(\Guard\Collect\Php\FunctionMetric\ArrowExpressionBoundary::class)]
#[UsesClass(\Guard\Collect\Php\FunctionMetric\ArrowFunctionMetricReader::class)]
#[UsesClass(\Guard\Collect\Php\FunctionMetric\BlockFunctionMetricReader::class)]
#[UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionBodyLocator::class)]
#[UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionMetric::class)]
#[UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionMetricCollector::class)]
#[UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionMetricComplexityAssigner::class)]
#[UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionMetricLineCollector::class)]
#[UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionNameReader::class)]
#[UsesClass(\Guard\Collect\Php\FunctionMetric\FunctionScanState::class)]
#[UsesClass(\Guard\Collect\Php\FunctionMetric\NestedFunctionMetricRange::class)]
#[UsesClass(\Guard\Collect\Php\PhpCollector::class)]
#[UsesClass(\Guard\Collect\Php\PhpSources::class)]
#[UsesClass(\Guard\Collect\Php\SourceMetricReader::class)]
#[UsesClass(\Guard\Collect\Php\SourceMetrics::class)]
#[UsesClass(\Guard\Collect\Php\Token\ClassLikeTokenMatcher::class)]
#[UsesClass(\Guard\Collect\Php\Token\CodeTokenLineResolver::class)]
#[UsesClass(\Guard\Collect\Php\Token\PhpTokenNavigator::class)]
#[UsesClass(\Guard\Collect\Php\Token\TokenLineCounter::class)]
#[UsesClass(\Guard\Collect\Tree\DirectoryTree::class)]
#[UsesClass(\Guard\Collect\Tree\Filesystem\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\Tree\Filesystem\DirectoryListingReader::class)]
#[UsesClass(\Guard\Collect\Tree\Filesystem\DirectoryTreeScanner::class)]
#[UsesClass(\Guard\Collect\Tree\Filesystem\PathInclusionPolicy::class)]
#[UsesClass(\Guard\Collect\Tree\Filesystem\PathResolver::class)]
#[UsesClass(\Guard\Collect\Tree\TreeCollector::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\ConfigurationLoader::class)]
#[UsesClass(\Guard\Config\Doc\ConfigKeyValidator::class)]
#[UsesClass(\Guard\Config\Doc\ConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Doc\DeclaredHeading::class)]
#[UsesClass(\Guard\Config\Doc\DeclaredHeadingReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentConfig::class)]
#[UsesClass(\Guard\Config\Doc\DocumentConfigReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentListConfigReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentationConfig::class)]
#[UsesClass(\Guard\Config\DocumentMerger::class)]
#[UsesClass(\Guard\Config\DocumentationReader::class)]
#[UsesClass(\Guard\Config\ImportResolver::class)]
#[UsesClass(\Guard\Config\Loc\ConfigKeyValidator::class)]
#[UsesClass(\Guard\Config\Loc\ConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Loc\ConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Loc\LimitConfig::class)]
#[UsesClass(\Guard\Config\Loc\LimitConfigReader::class)]
#[UsesClass(\Guard\Config\Loc\MetricsConfig::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyConfig::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyConfigReader::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyPolicyUsageValidator::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyRuleConfig::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyRuleConfigReader::class)]
#[UsesClass(\Guard\Config\Loc\Policy\ApplyRuleListConfigReader::class)]
#[UsesClass(\Guard\Config\Loc\Policy\PolicyConfig::class)]
#[UsesClass(\Guard\Config\Loc\Policy\PolicyConfigReader::class)]
#[UsesClass(\Guard\Config\Loc\Policy\PolicyDefinition::class)]
#[UsesClass(\Guard\Config\Loc\Policy\PolicyListConfigReader::class)]
#[UsesClass(\Guard\Config\Loc\Policy\PolicyResolver::class)]
#[UsesClass(\Guard\Config\Loc\ScanConfig::class)]
#[UsesClass(\Guard\Config\Loc\ScanConfigReader::class)]
#[UsesClass(\Guard\Config\MetricsReader::class)]
#[UsesClass(\Guard\Config\RuleReader::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Config\StructureReader::class)]
#[UsesClass(\Guard\Config\Tree\ConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Tree\ConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Tree\RuleConfig::class)]
#[UsesClass(\Guard\Config\Tree\RuleConfigReader::class)]
#[UsesClass(\Guard\Config\Tree\RuleListConfigReader::class)]
#[UsesClass(\Guard\Config\Tree\StructureConfig::class)]
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
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(\Guard\Extension\Registry::class)]
#[UsesClass(\Guard\Init\Initializer::class)]
#[UsesClass(\Guard\Init\LegacyMigration::class)]
#[UsesClass(\Guard\Init\Legacy\Doc\ConfigLoader::class)]
#[UsesClass(\Guard\Init\Legacy\Doc\ReportConfig::class)]
#[UsesClass(\Guard\Init\Legacy\Doc\ReportConfigReader::class)]
#[UsesClass(\Guard\Init\Legacy\Loc\ConfigLoader::class)]
#[UsesClass(\Guard\Init\Legacy\Loc\ReportConfig::class)]
#[UsesClass(\Guard\Init\Legacy\Loc\ReportConfigReader::class)]
#[UsesClass(\Guard\Init\Legacy\Tree\ConfigLoader::class)]
#[UsesClass(\Guard\Init\Legacy\Tree\ReportConfig::class)]
#[UsesClass(\Guard\Init\Legacy\Tree\ReportConfigReader::class)]
#[UsesClass(\Guard\Init\PresetCatalog::class)]
#[UsesClass(\Guard\Init\PresetOverrides::class)]
#[UsesClass(\Guard\Init\PresetSelector::class)]
#[UsesClass(\Guard\Init\Recommendations::class)]
#[UsesClass(\Guard\Init\ToolDetector::class)]
#[UsesClass(\Guard\Policy\Configuration\ConfigurationPolicy::class)]
#[UsesClass(\Guard\Policy\Constraint::class)]
#[UsesClass(\Guard\Policy\Doc\DocPolicy::class)]
#[UsesClass(\Guard\Policy\Doc\HeadingHunkClassifier::class)]
#[UsesClass(\Guard\Policy\Doc\HeadingSequenceAligner::class)]
#[UsesClass(\Guard\Policy\Doc\HeadingStructureComparator::class)]
#[UsesClass(\Guard\Policy\Doc\Violation::class)]
#[UsesClass(\Guard\Policy\Doc\ViolationFactory::class)]
#[UsesClass(\Guard\Policy\Loc\Assignment\ApplyRuleMatcher::class)]
#[UsesClass(\Guard\Policy\Loc\Assignment\FilePolicyAssigner::class)]
#[UsesClass(\Guard\Policy\Loc\Assignment\FilePolicyAssignment::class)]
#[UsesClass(\Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricLimit::class)]
#[UsesClass(\Guard\Policy\Loc\ClassLikeMetric\ClassLikeMetricViolationBuilder::class)]
#[UsesClass(\Guard\Policy\Loc\FileMetric\FileMetricViolationBuilder::class)]
#[UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionComplexityViolationBuilder::class)]
#[UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionLineViolationBuilder::class)]
#[UsesClass(\Guard\Policy\Loc\FunctionMetric\FunctionMetricViolationBuilder::class)]
#[UsesClass(\Guard\Policy\Loc\LocPolicy::class)]
#[UsesClass(\Guard\Policy\Loc\Violation::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Policy\RuleEvaluator::class)]
#[UsesClass(\Guard\Policy\Tree\CaseConventionMatcher::class)]
#[UsesClass(\Guard\Policy\Tree\ChildCountInspector::class)]
#[UsesClass(\Guard\Policy\Tree\DepthInspector::class)]
#[UsesClass(\Guard\Policy\Tree\DirNameInspector::class)]
#[UsesClass(\Guard\Policy\Tree\DirectoryPatternMatcher::class)]
#[UsesClass(\Guard\Policy\Tree\DirectoryRuleInspector::class)]
#[UsesClass(\Guard\Policy\Tree\EmptyDirectoryInspector::class)]
#[UsesClass(\Guard\Policy\Tree\FileNameInspector::class)]
#[UsesClass(\Guard\Policy\Tree\RequiredFileInspector::class)]
#[UsesClass(\Guard\Policy\Tree\TotalFileCountInspector::class)]
#[UsesClass(\Guard\Policy\Tree\TreePolicy::class)]
#[UsesClass(\Guard\Policy\Tree\Violation::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
#[UsesClass(\Guard\Reporting\Reporter::class)]
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

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testExecuteShowsCommandUsage(): void
    {
        $output = '';
        $app = new \Guard\Cli\Application(sys_get_temp_dir(), static function (string $text) use (&$output): void {
            $output .= $text;
        });
        self::assertSame(0, $app->execute(['command' => '--help', 'config' => 'guard.yaml', 'format' => 'text', 'dryRun' => false, 'imports' => null]));
        self::assertStringContainsString('check|apply|init', $output);
        self::assertStringContainsString('--import', $output);
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
