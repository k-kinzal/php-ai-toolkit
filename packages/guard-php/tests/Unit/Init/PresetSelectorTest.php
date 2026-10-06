<?php

declare(strict_types=1);

namespace Tests\Unit\Init;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\PresetSelector
 * @uses \Guard\Collect\Markdown\Filesystem\PathResolver
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
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Doc\ConfigKeyValidator
 * @uses \Guard\Config\Doc\ConfigStringListReader
 * @uses \Guard\Config\Doc\DeclaredHeading
 * @uses \Guard\Config\Doc\DeclaredHeadingReader
 * @uses \Guard\Config\Doc\DocumentConfig
 * @uses \Guard\Config\Doc\DocumentConfigReader
 * @uses \Guard\Config\Doc\DocumentListConfigReader
 * @uses \Guard\Config\Doc\DocumentationConfig
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
 * @uses \Guard\Config\Tree\ConfigScalarReader
 * @uses \Guard\Config\Tree\ConfigStringListReader
 * @uses \Guard\Config\Tree\RuleConfig
 * @uses \Guard\Config\Tree\RuleConfigReader
 * @uses \Guard\Config\Tree\RuleListConfigReader
 * @uses \Guard\Config\Tree\StructureConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
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
 * @uses \Guard\Init\Recommendations
 * @uses \Guard\Init\ToolDetector
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Init\PresetSelector::class)]
#[UsesClass(\Guard\Collect\Markdown\Filesystem\PathResolver::class)]
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
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Config\Doc\ConfigKeyValidator::class)]
#[UsesClass(\Guard\Config\Doc\ConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Doc\DeclaredHeading::class)]
#[UsesClass(\Guard\Config\Doc\DeclaredHeadingReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentConfig::class)]
#[UsesClass(\Guard\Config\Doc\DocumentConfigReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentListConfigReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentationConfig::class)]
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
#[UsesClass(\Guard\Config\Tree\ConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Tree\ConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Tree\RuleConfig::class)]
#[UsesClass(\Guard\Config\Tree\RuleConfigReader::class)]
#[UsesClass(\Guard\Config\Tree\RuleListConfigReader::class)]
#[UsesClass(\Guard\Config\Tree\StructureConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
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
#[UsesClass(\Guard\Init\Recommendations::class)]
#[UsesClass(\Guard\Init\ToolDetector::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
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
        self::assertSame(['metrics', 'structure', 'phpstan', 'phpstan-guard-rules', 'phpunit9', 'composer'], $names);
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
