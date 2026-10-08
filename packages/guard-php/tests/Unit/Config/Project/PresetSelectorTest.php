<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project;

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
#[CoversClass(\Guard\Config\Project\PresetSelector::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Path::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
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
#[UsesClass(\Guard\Config\Reader\DirectoryRuleConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\DirectoryRuleListConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\DocumentConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\DocumentListConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\LimitConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\ScanConfigReader::class)]
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
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
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
#[UsesClass(\Guard\Config\Project\Recommendations::class)]
#[UsesClass(\Guard\Config\Project\ToolDetector::class)]
#[UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
#[UsesClass(\Guard\Structure\Markdown\AtxHeadingMatcher::class)]
#[UsesClass(\Guard\Structure\Markdown\BlockMarkerMatcher::class)]
#[UsesClass(\Guard\Structure\Markdown\Block\BlockLineScanner::class)]
#[UsesClass(\Guard\Structure\Markdown\Fence::class)]
#[UsesClass(\Guard\Structure\Markdown\FenceMatcher::class)]
#[UsesClass(\Guard\Structure\Markdown\Heading::class)]
#[UsesClass(\Guard\Structure\Markdown\HeadingParser::class)]
#[UsesClass(\Guard\Structure\Markdown\HeadingTextNormalizer::class)]
#[UsesClass(\Guard\Structure\Markdown\HtmlBlockMatcher::class)]
#[UsesClass(\Guard\Structure\Markdown\LineIndentation::class)]
#[UsesClass(\Guard\Structure\Markdown\LineScanner::class)]
#[UsesClass(\Guard\Structure\Markdown\MarkdownLineSplitter::class)]
#[UsesClass(\Guard\Structure\Markdown\ParserState::class)]
#[UsesClass(\Guard\Structure\Markdown\SetextUnderlineMatcher::class)]
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
        $names = (new \Guard\Config\Project\PresetSelector())->detect($root);
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
        self::assertSame(['agents-md', 'claude-md', 'readme-md'], (new \Guard\Config\Project\PresetSelector())->detect($root));
    }

    public function testDocumentPresetsSelectsAgentsForEitherAgentFile(): void
    {
        $root = sys_get_temp_dir() . '/guard-document-presets-' . uniqid();
        mkdir($root);
        $selector = new \Guard\Config\Project\PresetSelector();
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
        self::assertSame(['disable-doc'], (new \Guard\Config\Project\PresetSelector())->detect($root));
    }

    /**
     * @throws JsonException
     */
    public function testDetectKeepsExistingEmptyDocumentationAllowed(): void
    {
        $root = sys_get_temp_dir() . '/guard-existing-docs-' . uniqid();
        mkdir($root . '/docs', 0777, true);
        self::assertSame([], (new \Guard\Config\Project\PresetSelector())->detect($root));
    }

    /**
     * @throws JsonException
     */
    public function testDetectKeepsExistingDocumentationAllowed(): void
    {
        $root = sys_get_temp_dir() . '/guard-populated-docs-' . uniqid();
        mkdir($root . '/docs', 0777, true);
        file_put_contents($root . '/docs/guide.md', '# Guide');
        self::assertSame([], (new \Guard\Config\Project\PresetSelector())->detect($root));
    }

    /**
     * @throws JsonException
     */
    public function testSelectRespectsExplicitImportsRegardlessOfDocsPresence(): void
    {
        $root = sys_get_temp_dir() . '/guard-explicit-docs-' . uniqid();
        mkdir($root);
        $selector = new \Guard\Config\Project\PresetSelector();
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
        $names = (new \Guard\Config\Project\PresetSelector())->select($root, ['metrics', 'structure', 'composer', 'metrics']);
        self::assertSame(['composer'], $names);
    }

    /**
     * @throws JsonException
     */
    public function testSelectRejectsAnUnknownExplicitName(): void
    {
        $root = sys_get_temp_dir() . '/guard-select-unknown-' . uniqid();
        mkdir($root);
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $this->expectExceptionMessage('Unknown guard import "nope"');
        (new \Guard\Config\Project\PresetSelector())->select($root, ['nope']);
    }

    /**
     * @throws JsonException
     */
    public function testPhpstanPresetsSkipsProjectsWithoutNeon(): void
    {
        $root = sys_get_temp_dir() . '/guard-no-neon-' . uniqid();
        mkdir($root);
        self::assertSame([], (new \Guard\Config\Project\PresetSelector())->phpstanPresets($root));
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
        $selector = new \Guard\Config\Project\PresetSelector();
        self::assertSame(['phpunit10'], $selector->phpunitPresets($root));
        self::assertSame([], $selector->phpunitPresets($root . '-missing'));
    }

    public function testPhpunitMajorIgnoresAMultiMajorConstraint(): void
    {
        $selector = new \Guard\Config\Project\PresetSelector();
        self::assertSame('10', $selector->phpunitMajor('10.5.0'));
        self::assertSame('13', $selector->phpunitMajor('^13.0'));
        self::assertNull($selector->phpunitMajor('^9.6 || ^10.5 || ^11'));
    }

    public function testDoctestPresetsFollowsASuiteOrRequiredExamples(): void
    {
        $root = sys_get_temp_dir() . '/guard-doctest-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpunit10.xml.dist', '<phpunit><file>src/Doctest/DoctestSuite.php</file></phpunit>');
        $selector = new \Guard\Config\Project\PresetSelector();
        self::assertSame(['doctest10'], $selector->doctestPresets($root, ['phpunit10']));
    }

    public function testRequiresExamplesReadsThePhpStanFlag(): void
    {
        $root = sys_get_temp_dir() . '/guard-examples-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpstan.neon', "parameters:\n    toolkit:\n        allRules: true\n");
        self::assertTrue((new \Guard\Config\Project\PresetSelector())->requiresExamples($root));
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
        $names = (new \Guard\Config\Project\PresetSelector())->toolPresets($root);
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
        self::assertSame('k-kinzal/docgen-php', (new \Guard\Config\Project\PresetSelector())->packageName($root));
    }

    public function testUniqueDropsRepeatedNames(): void
    {
        self::assertSame(['metrics', 'composer'], (new \Guard\Config\Project\PresetSelector())->unique(['metrics', 'composer', 'metrics']));
    }

    public function testWithoutRemovesOnePreset(): void
    {
        self::assertSame(['structure'], (new \Guard\Config\Project\PresetSelector())->without(['metrics', 'structure'], 'metrics'));
    }
}
