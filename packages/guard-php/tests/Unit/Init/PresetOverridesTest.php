<?php

declare(strict_types=1);

namespace Tests\Unit\Init;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\PresetOverrides
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
 * @uses \Guard\Init\PresetSelector
 * @uses \Guard\Init\Recommendations
 * @uses \Guard\Init\ToolDetector
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Init\PresetOverrides::class)]
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
#[UsesClass(\Guard\Init\PresetSelector::class)]
#[UsesClass(\Guard\Init\Recommendations::class)]
#[UsesClass(\Guard\Init\ToolDetector::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
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
