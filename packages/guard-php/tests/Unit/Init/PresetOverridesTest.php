<?php

declare(strict_types=1);

namespace Tests\Unit\Init;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\PresetOverrides
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
 * @uses \Guard\Init\PresetSelector
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
#[CoversClass(\Guard\Init\PresetOverrides::class)]
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
#[UsesClass(\Guard\Init\PresetSelector::class)]
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
        $document = (new \Guard\Init\PresetOverrides())->apply($root, ['phpunit10'], ['version' => 1]);
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
        $rules = (new \Guard\Init\PresetOverrides())->directories($root, ['structure']);
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
        $document = (new \Guard\Init\PresetOverrides())->apply($root, ['composer'], ['version' => 1]);
        self::assertArrayNotHasKey('structure', $document);
        self::assertArrayNotHasKey('configuration', $document);
    }

    public function testFilesOverridesThePhpStanDistPath(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-neon-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpstan.neon.dist', "parameters:\n    level: 5\n");
        $rules = (new \Guard\Init\PresetOverrides())->files($root, ['phpstan', 'phpstan-guard-rules']);
        self::assertSame('phpstan.level', $rules[0]['id']);
        self::assertSame('phpstan.neon.dist', $rules[0]['file']);
        self::assertSame('phpstan.all-rules', $rules[4]['id']);
    }

    public function testActualUsesThePlainPhpUnitFileWhenTheVersionedOneIsMissing(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-actual-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpunit.xml.dist', '<phpunit/>');
        self::assertSame('phpunit.xml.dist', (new \Guard\Init\PresetOverrides())->actual($root, 'phpunit10'));
    }

    public function testPbtExcludesOnlyExistingPhpUnitFiles(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-pbt-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpunit.xml.dist', '<phpunit/>');
        $rules = (new \Guard\Init\PresetOverrides())->pbtExcludes($root, ['pbt']);
        self::assertSame('phpunit.xml.dist', $rules[0]['file']);
        self::assertSame([], (new \Guard\Init\PresetOverrides())->pbtExcludes($root, ['composer']));
    }

    public function testRefileSkipsPresetsThatWereNotSelected(): void
    {
        self::assertSame([], (new \Guard\Init\PresetOverrides())->refile(['composer'], ['phpstan'], 'phpstan.neon.dist'));
    }
}
