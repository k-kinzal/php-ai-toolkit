<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project;

use Guard\Config\Configuration;
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
use Guard\Config\Reader\DirectoryRuleConfigReader;
use Guard\Config\Reader\DirectoryRuleListConfigReader;
use Guard\Config\Reader\DocumentConfigReader;
use Guard\Config\Reader\DocumentListConfigReader;
use Guard\Config\Reader\LimitConfigReader;
use Guard\Config\Reader\ScanConfigReader;
use Guard\Config\Validation\DirectoryConfigScalarReader;
use Guard\Config\Validation\DirectoryConfigStringListReader;
use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Config\Validation\HeadingConfigStringListReader;
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
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
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
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use Guard\Structure\Markdown\AtxHeadingMatcher;
use Guard\Structure\Markdown\Block\BlockLineScanner;
use Guard\Structure\Markdown\BlockMarkerMatcher;
use Guard\Structure\Markdown\Fence;
use Guard\Structure\Markdown\FenceMatcher;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingParser;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use Guard\Structure\Markdown\HtmlBlockMatcher;
use Guard\Structure\Markdown\LineIndentation;
use Guard\Structure\Markdown\LineScanner;
use Guard\Structure\Markdown\MarkdownLineSplitter;
use Guard\Structure\Markdown\ParserState;
use Guard\Structure\Markdown\SetextUnderlineMatcher;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Project\PresetSelector
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Path
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
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
 * @uses \Guard\Config\Reader\DirectoryRuleConfigReader
 * @uses \Guard\Config\Reader\DirectoryRuleListConfigReader
 * @uses \Guard\Config\Reader\DocumentConfigReader
 * @uses \Guard\Config\Reader\DocumentListConfigReader
 * @uses \Guard\Config\Reader\LimitConfigReader
 * @uses \Guard\Config\Reader\ScanConfigReader
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
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
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
 * @uses \Guard\Config\Project\Recommendations
 * @uses \Guard\Config\Project\ToolDetector
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Structure\Markdown\AtxHeadingMatcher
 * @uses \Guard\Structure\Markdown\BlockMarkerMatcher
 * @uses \Guard\Structure\Markdown\Block\BlockLineScanner
 * @uses \Guard\Structure\Markdown\Fence
 * @uses \Guard\Structure\Markdown\FenceMatcher
 * @uses \Guard\Structure\Markdown\Heading
 * @uses \Guard\Structure\Markdown\HeadingParser
 * @uses \Guard\Structure\Markdown\HeadingTextNormalizer
 * @uses \Guard\Structure\Markdown\HtmlBlockMatcher
 * @uses \Guard\Structure\Markdown\LineIndentation
 * @uses \Guard\Structure\Markdown\LineScanner
 * @uses \Guard\Structure\Markdown\MarkdownLineSplitter
 * @uses \Guard\Structure\Markdown\ParserState
 * @uses \Guard\Structure\Markdown\SetextUnderlineMatcher
 */
#[CoversClass(PresetSelector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Path::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
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
#[UsesClass(DirectoryRuleConfigReader::class)]
#[UsesClass(DirectoryRuleListConfigReader::class)]
#[UsesClass(DocumentConfigReader::class)]
#[UsesClass(DocumentListConfigReader::class)]
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(ScanConfigReader::class)]
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
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
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
#[UsesClass(Recommendations::class)]
#[UsesClass(ToolDetector::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(BlockMarkerMatcher::class)]
#[UsesClass(BlockLineScanner::class)]
#[UsesClass(Fence::class)]
#[UsesClass(FenceMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingParser::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(HtmlBlockMatcher::class)]
#[UsesClass(LineIndentation::class)]
#[UsesClass(LineScanner::class)]
#[UsesClass(MarkdownLineSplitter::class)]
#[UsesClass(ParserState::class)]
#[UsesClass(SetextUnderlineMatcher::class)]
final class PresetSelectorTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testDetectMatchesInstalledTools(): void
    {
        $root = sys_get_temp_dir() . '/guard-detect-' . uniqid();
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/composer.json', '{"require-dev":{"phpunit/phpunit":"9.6.0","k-kinzal/phpstan-guard-rules":"^1.0"}}');
        file_put_contents($root . '/phpstan.neon', "parameters:\n    level: max\n");
        file_put_contents($root . '/phpunit.xml.dist', '<phpunit/>');
        $names = (new PresetSelector())->detect($root);
        self::assertSame(['metrics', 'structure', 'disable-doc', 'phpstan', 'phpstan-guard-rules', 'phpunit9', 'composer'], $names);
    }

    /**
     * @throws JsonException
     */
    public function testDetectAppendsTheMarkdownPresetsOfExistingDocuments(): void
    {
        $root = sys_get_temp_dir() . '/guard-detect-markdown-' . uniqid();
        mkdir($root . '/docs', 0777, true);
        file_put_contents($root . '/README.md', '# Tool');
        file_put_contents($root . '/CLAUDE.md', '@AGENTS.md');
        self::assertSame(['agents-md', 'claude-md', 'readme-md'], (new PresetSelector())->detect($root));
    }

    public function testDocumentPresetsSelectsAgentsForEitherAgentFile(): void
    {
        $root = sys_get_temp_dir() . '/guard-document-presets-' . uniqid();
        mkdir($root);
        $selector = new PresetSelector();
        self::assertSame([], $selector->documentPresets($root));
        file_put_contents($root . '/AGENTS.md', '# AGENTS');
        self::assertSame(['agents-md'], $selector->documentPresets($root));
        file_put_contents($root . '/CLAUDE.md', '@AGENTS.md');
        unlink($root . '/AGENTS.md');
        self::assertSame(['agents-md', 'claude-md'], $selector->documentPresets($root));
    }

    /**
     * @throws JsonException
     */
    public function testDetectDisablesDocsWithoutSourceDirectories(): void
    {
        $root = sys_get_temp_dir() . '/guard-no-docs-' . uniqid();
        mkdir($root);
        self::assertSame(['disable-doc'], (new PresetSelector())->detect($root));
    }

    /**
     * @throws JsonException
     */
    public function testDetectKeepsExistingEmptyDocumentationAllowed(): void
    {
        $root = sys_get_temp_dir() . '/guard-existing-docs-' . uniqid();
        mkdir($root . '/docs', 0777, true);
        self::assertSame([], (new PresetSelector())->detect($root));
    }

    /**
     * @throws JsonException
     */
    public function testDetectKeepsExistingDocumentationAllowed(): void
    {
        $root = sys_get_temp_dir() . '/guard-populated-docs-' . uniqid();
        mkdir($root . '/docs', 0777, true);
        file_put_contents($root . '/docs/guide.md', '# Guide');
        self::assertSame([], (new PresetSelector())->detect($root));
    }

    /**
     * @throws JsonException
     */
    public function testSelectRespectsExplicitImportsRegardlessOfDocsPresence(): void
    {
        $root = sys_get_temp_dir() . '/guard-explicit-docs-' . uniqid();
        mkdir($root);
        $selector = new PresetSelector();
        self::assertSame([], $selector->select($root, []));
        self::assertSame(['composer'], $selector->select($root, ['composer']));
        mkdir($root . '/docs');
        self::assertSame(['disable-doc'], $selector->select($root, ['disable-doc', 'disable-doc']));
    }

    /**
     * @throws JsonException
     */
    public function testSelectDropsPresetsReplacedByLegacyFiles(): void
    {
        $root = sys_get_temp_dir() . '/guard-select-legacy-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/loc.yaml', "scan: {roots: [lib]}\n");
        file_put_contents($root . '/tree.yaml', "paths: [src]\n");
        $names = (new PresetSelector())->select($root, ['metrics', 'structure', 'composer', 'metrics']);
        self::assertSame(['composer'], $names);
    }

    /**
     * @throws JsonException
     */
    public function testSelectRejectsAnUnknownExplicitName(): void
    {
        $root = sys_get_temp_dir() . '/guard-select-unknown-' . uniqid();
        mkdir($root);
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Unknown guard import "nope"');
        (new PresetSelector())->select($root, ['nope']);
    }

    /**
     * @throws JsonException
     */
    public function testPhpstanPresetsSkipsProjectsWithoutNeon(): void
    {
        $root = sys_get_temp_dir() . '/guard-no-neon-' . uniqid();
        mkdir($root);
        self::assertSame([], (new PresetSelector())->phpstanPresets($root));
    }

    /**
     * @throws JsonException
     */
    /**
     * @throws JsonException
     */
    public function testPhpunitPresetsUsesTheResolvedMajorForAnUnversionedFile(): void
    {
        $root = sys_get_temp_dir() . '/guard-phpunit-modern-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/composer.json', '{"require-dev":{"phpunit/phpunit":"^10.5"}}');
        file_put_contents($root . '/phpunit.xml.dist', '<phpunit/>');
        $selector = new PresetSelector();
        self::assertSame(['phpunit10'], $selector->phpunitPresets($root));
        self::assertSame([], $selector->phpunitPresets($root . '-missing'));
    }

    public function testPhpunitMajorIgnoresAMultiMajorConstraint(): void
    {
        $selector = new PresetSelector();
        self::assertSame('10', $selector->phpunitMajor('10.5.0'));
        self::assertSame('13', $selector->phpunitMajor('^13.0'));
        self::assertNull($selector->phpunitMajor('^9.6 || ^10.5 || ^11'));
    }

    public function testDoctestPresetsFollowsASuiteOrRequiredExamples(): void
    {
        $root = sys_get_temp_dir() . '/guard-doctest-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpunit10.xml.dist', '<phpunit><file>src/Doctest/DoctestSuite.php</file></phpunit>');
        $selector = new PresetSelector();
        self::assertSame(['doctest10'], $selector->doctestPresets($root, ['phpunit10']));
    }

    public function testRequiresExamplesReadsThePhpStanFlag(): void
    {
        $root = sys_get_temp_dir() . '/guard-examples-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpstan.neon', "parameters:\n    toolkit:\n        allRules: true\n");
        self::assertTrue((new PresetSelector())->requiresExamples($root));
    }

    /**
     * @throws JsonException
     */
    public function testToolPresetsMatchesInstalledFiles(): void
    {
        $root = sys_get_temp_dir() . '/guard-tools-' . uniqid();
        mkdir($root . '/.github/workflows', 0777, true);
        file_put_contents($root . '/composer.json', '{"require-dev":{"deptrac/deptrac":"^2.0","giorgiosironi/eris":"^1.0"}}');
        file_put_contents($root . '/deptrac.yaml', "deptrac: {}\n");
        file_put_contents($root . '/infection.json5', "{}\n");
        file_put_contents($root . '/.github/workflows/ci.yml', "name: CI\n");
        $names = (new PresetSelector())->toolPresets($root);
        self::assertSame(['deptrac', 'infection', 'github-actions', 'pbt'], $names);
    }

    /**
     * @throws JsonException
     */
    public function testPackageNameReadsComposer(): void
    {
        $root = sys_get_temp_dir() . '/guard-package-name-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/composer.json', '{"name":"k-kinzal/docgen-php"}');
        self::assertSame('k-kinzal/docgen-php', (new PresetSelector())->packageName($root));
    }

    public function testUniqueDropsRepeatedNames(): void
    {
        self::assertSame(['metrics', 'composer'], (new PresetSelector())->unique(['metrics', 'composer', 'metrics']));
    }

    public function testWithoutRemovesOnePreset(): void
    {
        self::assertSame(['structure'], (new PresetSelector())->without(['metrics', 'structure'], 'metrics'));
    }
}
