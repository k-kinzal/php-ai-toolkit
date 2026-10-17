<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

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
use Guard\Structure\Markdown\AtxHeadingMatcher;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingList;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use Guard\Structure\ParsedDocument;
use Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric;
use Guard\Structure\Php\FileMetric\FileMetric;
use Guard\Structure\Php\FunctionMetric\FunctionMetric;
use Guard\Structure\Php\SourceMetrics;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\ConfigurationLoader
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Path
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\PathPatternMatcher
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Policy\Assignment\ApplyRuleMatcher
 * @uses \Guard\Policy\Assignment\FilePolicyAssigner
 * @uses \Guard\Policy\Assignment\FilePolicyAssignment
 * @uses \Guard\Config\Configuration
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
 * @uses \Guard\Structure\Markdown\AtxHeadingMatcher
 * @uses \Guard\Structure\Markdown\Heading
 * @uses \Guard\Structure\Markdown\HeadingList
 * @uses \Guard\Structure\Markdown\HeadingTextNormalizer
 * @uses \Guard\Structure\ParsedDocument
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric
 * @uses \Guard\Structure\Php\FileMetric\FileMetric
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Structure\Php\SourceMetrics
 * @uses \Guard\Policy\Diagnostic\FieldMessage
 */
#[CoversClass(ConfigurationLoader::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Path::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(PathPatternMatcher::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(ApplyRuleMatcher::class)]
#[UsesClass(FilePolicyAssigner::class)]
#[UsesClass(FilePolicyAssignment::class)]
#[UsesClass(Configuration::class)]
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
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingList::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(ParsedDocument::class)]
#[UsesClass(ClassLikeMetric::class)]
#[UsesClass(FileMetric::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(SourceMetrics::class)]
#[UsesClass(FieldMessage::class)]
final class ConfigurationLoaderTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRejectsUnknownTopLevelKeys(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-config-');
        self::assertIsString($path);
        file_put_contents($path, "version: 1\nconfiguraiton: []\n");
        $this->expectException(PolicyException::class);
        (new ConfigurationLoader())->load($path);
    }

    /**
     * @throws JsonException
     */
    public function testLoadsAConfigurationOnlyPolicy(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-config-');
        self::assertIsString($path);
        file_put_contents($path, "version: 1\nconfiguration:\n  - id: workers\n    file: app.json\n    select: /workers\n    assert: {min: 1}\n    repair: 1\n");
        $config = (new ConfigurationLoader())->load($path);
        self::assertCount(1, $config->policies);
        self::assertInstanceOf(FieldConstraints::class, $config->policies[0]->policy);
    }

    /**
     * @throws JsonException
     */
    public function testLoadsImportedRulesAndProjectOverrides(): void
    {
        $root = sys_get_temp_dir() . '/guard-import-load-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/preset.yaml', "configuration:\n  - {id: mode, file: app.json, select: /mode, assert: {equals: A}}\n");
        file_put_contents($root . '/guard.yaml', "version: 1\nimports: [preset.yaml]\nconfiguration:\n  - {id: mode, file: settings.json}\n");
        $config = (new ConfigurationLoader())->load($root . '/guard.yaml');
        $requests = $config->policies[0]->policy->inputs(new Context($config->root, $root . '/guard.yaml', false, $config->scope));
        self::assertSame(['settings.json'], $requests['file0']->selection->paths);
    }

}
