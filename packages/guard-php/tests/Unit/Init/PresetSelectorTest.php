<?php

declare(strict_types=1);

namespace Tests\Unit\Init;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\PresetSelector
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Filesystem\Path
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
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
 * @uses \Guard\Config\Value\DeclaredHeading
 * @uses \Guard\Config\Value\DirectoryRuleConfig
 * @uses \Guard\Config\Value\DocumentConfig
 * @uses \Guard\Config\Value\DocumentationConfig
 * @uses \Guard\Config\Value\LimitConfig
 * @uses \Guard\Config\Value\MetricsConfig
 * @uses \Guard\Config\Value\ScanConfig
 * @uses \Guard\Config\Value\StructureConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
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
 * @uses \Guard\Init\Recommendations
 * @uses \Guard\Init\ToolDetector
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
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
#[CoversClass(\Guard\Init\PresetSelector::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Filesystem\Path::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
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
#[UsesClass(\Guard\Config\Value\DeclaredHeading::class)]
#[UsesClass(\Guard\Config\Value\DirectoryRuleConfig::class)]
#[UsesClass(\Guard\Config\Value\DocumentConfig::class)]
#[UsesClass(\Guard\Config\Value\DocumentationConfig::class)]
#[UsesClass(\Guard\Config\Value\LimitConfig::class)]
#[UsesClass(\Guard\Config\Value\MetricsConfig::class)]
#[UsesClass(\Guard\Config\Value\ScanConfig::class)]
#[UsesClass(\Guard\Config\Value\StructureConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
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
#[UsesClass(\Guard\Init\Recommendations::class)]
#[UsesClass(\Guard\Init\ToolDetector::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
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
        $names = (new \Guard\Init\PresetSelector())->detect($root);
        self::assertSame(['metrics', 'structure', 'disable-doc', 'phpstan', 'phpstan-guard-rules', 'phpunit9', 'composer'], $names);
    }

    /**
     * @throws JsonException
     */
    public function testDetectDisablesDocsWithoutSourceDirectories(): void
    {
        $root = sys_get_temp_dir() . '/guard-no-docs-' . uniqid();
        mkdir($root);
        self::assertSame(['disable-doc'], (new \Guard\Init\PresetSelector())->detect($root));
    }

    /**
     * @throws JsonException
     */
    public function testDetectKeepsExistingEmptyDocumentationAllowed(): void
    {
        $root = sys_get_temp_dir() . '/guard-existing-docs-' . uniqid();
        mkdir($root . '/docs', 0777, true);
        self::assertSame([], (new \Guard\Init\PresetSelector())->detect($root));
    }

    /**
     * @throws JsonException
     */
    public function testDetectKeepsExistingDocumentationAllowed(): void
    {
        $root = sys_get_temp_dir() . '/guard-populated-docs-' . uniqid();
        mkdir($root . '/docs', 0777, true);
        file_put_contents($root . '/docs/guide.md', '# Guide');
        self::assertSame([], (new \Guard\Init\PresetSelector())->detect($root));
    }

    /**
     * @throws JsonException
     */
    public function testSelectRespectsExplicitImportsRegardlessOfDocsPresence(): void
    {
        $root = sys_get_temp_dir() . '/guard-explicit-docs-' . uniqid();
        mkdir($root);
        $selector = new \Guard\Init\PresetSelector();
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
        $names = (new \Guard\Init\PresetSelector())->select($root, ['metrics', 'structure', 'composer', 'metrics']);
        self::assertSame(['composer'], $names);
    }

    /**
     * @throws JsonException
     */
    public function testSelectRejectsAnUnknownExplicitName(): void
    {
        $root = sys_get_temp_dir() . '/guard-select-unknown-' . uniqid();
        mkdir($root);
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('Unknown guard import "nope"');
        (new \Guard\Init\PresetSelector())->select($root, ['nope']);
    }

    /**
     * @throws JsonException
     */
    public function testPhpstanPresetsSkipsProjectsWithoutNeon(): void
    {
        $root = sys_get_temp_dir() . '/guard-no-neon-' . uniqid();
        mkdir($root);
        self::assertSame([], (new \Guard\Init\PresetSelector())->phpstanPresets($root));
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
        $selector = new \Guard\Init\PresetSelector();
        self::assertSame(['phpunit10'], $selector->phpunitPresets($root));
        self::assertSame([], $selector->phpunitPresets($root . '-missing'));
    }

    public function testPhpunitMajorIgnoresAMultiMajorConstraint(): void
    {
        $selector = new \Guard\Init\PresetSelector();
        self::assertSame('10', $selector->phpunitMajor('10.5.0'));
        self::assertSame('13', $selector->phpunitMajor('^13.0'));
        self::assertNull($selector->phpunitMajor('^9.6 || ^10.5 || ^11'));
    }

    public function testDoctestPresetsFollowsASuiteOrRequiredExamples(): void
    {
        $root = sys_get_temp_dir() . '/guard-doctest-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpunit10.xml.dist', '<phpunit><file>src/Doctest/DoctestSuite.php</file></phpunit>');
        $selector = new \Guard\Init\PresetSelector();
        self::assertSame(['doctest10'], $selector->doctestPresets($root, ['phpunit10']));
    }

    public function testRequiresExamplesReadsThePhpStanFlag(): void
    {
        $root = sys_get_temp_dir() . '/guard-examples-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpstan.neon', "parameters:\n    toolkit:\n        allRules: true\n");
        self::assertTrue((new \Guard\Init\PresetSelector())->requiresExamples($root));
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
        $names = (new \Guard\Init\PresetSelector())->toolPresets($root);
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
        self::assertSame('k-kinzal/docgen-php', (new \Guard\Init\PresetSelector())->packageName($root));
    }

    public function testUniqueDropsRepeatedNames(): void
    {
        self::assertSame(['metrics', 'composer'], (new \Guard\Init\PresetSelector())->unique(['metrics', 'composer', 'metrics']));
    }

    public function testWithoutRemovesOnePreset(): void
    {
        self::assertSame(['structure'], (new \Guard\Init\PresetSelector())->without(['metrics', 'structure'], 'metrics'));
    }
}
