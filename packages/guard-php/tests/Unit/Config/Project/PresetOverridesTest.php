<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Project\PresetOverrides
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
 * @uses \Guard\Config\Project\PresetSelector
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
#[CoversClass(\Guard\Config\Project\PresetOverrides::class)]
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
#[UsesClass(\Guard\Config\Project\PresetSelector::class)]
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
final class PresetOverridesTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testApplyPointsPhpUnitRulesAtTheDetectedFile(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-phpunit-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpunit.xml', '<phpunit/>');
        $document = (new \Guard\Config\Project\PresetOverrides())->apply($root, ['phpunit10'], ['version' => 1]);
        $encoded = json_encode($document['configuration'], JSON_THROW_ON_ERROR);
        self::assertStringContainsString('"file":"phpunit.xml"', $encoded);
        self::assertStringContainsString('phpunit10.beStrictAboutChangesToGlobalState', $encoded);
        self::assertStringNotContainsString('assert', $encoded);
    }

    /**
     * @throws JsonException
     */
    public function testDirectoriesAddsRulesForOtherSourceRoots(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-lib-' . uniqid();
        mkdir($root . '/lib', 0777, true);
        file_put_contents($root . '/composer.json', '{"autoload":{"psr-4":{"Lib\\\\":"lib/"}}}');
        $rules = (new \Guard\Config\Project\PresetOverrides())->directories($root, ['structure']);
        self::assertSame('lib', $rules[0]['path']);
        self::assertSame('lib/**', $rules[1]['path']);
    }
    /**
     * @throws JsonException
     */
    public function testApplySkipsStructureWhenThatPresetIsAbsent(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-skip-' . uniqid();
        mkdir($root . '/lib', 0777, true);
        file_put_contents($root . '/composer.json', '{"autoload":{"psr-4":{"Lib\\\\":"lib/"}}}');
        $document = (new \Guard\Config\Project\PresetOverrides())->apply($root, ['composer'], ['version' => 1]);
        self::assertArrayNotHasKey('structure', $document);
        self::assertArrayNotHasKey('configuration', $document);
    }

    public function testFilesOverridesThePhpStanDistPath(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-neon-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpstan.neon.dist', "parameters:\n    level: 5\n");
        $rules = (new \Guard\Config\Project\PresetOverrides())->files($root, ['phpstan', 'phpstan-guard-rules']);
        self::assertSame('phpstan.level', $rules[0]['id']);
        self::assertSame('phpstan.neon.dist', $rules[0]['file']);
        self::assertSame('phpstan.all-rules', $rules[4]['id']);
    }

    public function testActualUsesThePlainPhpUnitFileWhenTheVersionedOneIsMissing(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-actual-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpunit.xml.dist', '<phpunit/>');
        self::assertSame('phpunit.xml.dist', (new \Guard\Config\Project\PresetOverrides())->actual($root, 'phpunit10'));
    }

    public function testPbtExcludesOnlyExistingPhpUnitFiles(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-pbt-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpunit.xml.dist', '<phpunit/>');
        $rules = (new \Guard\Config\Project\PresetOverrides())->pbtExcludes($root, ['pbt']);
        self::assertSame('phpunit.xml.dist', $rules[0]['file']);
        self::assertSame([], (new \Guard\Config\Project\PresetOverrides())->pbtExcludes($root, ['composer']));
    }

    public function testRefileSkipsPresetsThatWereNotSelected(): void
    {
        self::assertSame([], (new \Guard\Config\Project\PresetOverrides())->refile(['composer'], ['phpstan'], 'phpstan.neon.dist'));
    }
}
