<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

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
#[CoversClass(\Guard\Config\ConfigurationLoader::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Path::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\PathPatternMatcher::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Policy\Assignment\ApplyRuleMatcher::class)]
#[UsesClass(\Guard\Policy\Assignment\FilePolicyAssigner::class)]
#[UsesClass(\Guard\Policy\Assignment\FilePolicyAssignment::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
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
#[UsesClass(\Guard\Config\RuleReader::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Config\Validation\DirectoryConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Validation\DirectoryConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Validation\HeadingConfigKeyValidator::class)]
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
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
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
#[UsesClass(\Guard\Structure\Markdown\AtxHeadingMatcher::class)]
#[UsesClass(\Guard\Structure\Markdown\Heading::class)]
#[UsesClass(\Guard\Structure\Markdown\HeadingList::class)]
#[UsesClass(\Guard\Structure\Markdown\HeadingTextNormalizer::class)]
#[UsesClass(\Guard\Structure\ParsedDocument::class)]
#[UsesClass(\Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric::class)]
#[UsesClass(\Guard\Structure\Php\FileMetric\FileMetric::class)]
#[UsesClass(\Guard\Structure\Php\FunctionMetric\FunctionMetric::class)]
#[UsesClass(\Guard\Structure\Php\SourceMetrics::class)]
#[UsesClass(\Guard\Policy\Diagnostic\FieldMessage::class)]
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
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        (new \Guard\Config\ConfigurationLoader())->load($path);
    }

    /**
     * @throws JsonException
     */
    public function testLoadsAConfigurationOnlyPolicy(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-config-');
        self::assertIsString($path);
        file_put_contents($path, "version: 1\nconfiguration:\n  - id: workers\n    file: app.json\n    select: /workers\n    assert: {min: 1}\n    repair: 1\n");
        $config = (new \Guard\Config\ConfigurationLoader())->load($path);
        self::assertCount(1, $config->policies);
        self::assertInstanceOf(\Guard\Policy\FieldConstraints::class, $config->policies[0]->policy);
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
        $config = (new \Guard\Config\ConfigurationLoader())->load($root . '/guard.yaml');
        $requests = $config->policies[0]->policy->inputs(new \Guard\Policy\Context($config->root, $root . '/guard.yaml', false, $config->scope));
        self::assertSame(['settings.json'], $requests['file0']->selection->paths);
    }

}
