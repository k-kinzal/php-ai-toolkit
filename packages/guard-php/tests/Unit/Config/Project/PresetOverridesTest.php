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
#[CoversClass(PresetOverrides::class)]
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
#[UsesClass(PresetSelector::class)]
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
        $document = (new PresetOverrides())->apply($root, ['phpunit10'], ['version' => 1]);
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
        $rules = (new PresetOverrides())->directories($root, ['structure']);
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
        $document = (new PresetOverrides())->apply($root, ['composer'], ['version' => 1]);
        self::assertArrayNotHasKey('structure', $document);
        self::assertArrayNotHasKey('configuration', $document);
    }

    public function testFilesOverridesThePhpStanDistPath(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-neon-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpstan.neon.dist', "parameters:\n    level: 5\n");
        $rules = (new PresetOverrides())->files($root, ['phpstan', 'phpstan-guard-rules']);
        self::assertSame('phpstan.level', $rules[0]['id']);
        self::assertSame('phpstan.neon.dist', $rules[0]['file']);
        self::assertSame('phpstan.all-rules', $rules[4]['id']);
    }

    public function testActualUsesThePlainPhpUnitFileWhenTheVersionedOneIsMissing(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-actual-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpunit.xml.dist', '<phpunit/>');
        self::assertSame('phpunit.xml.dist', (new PresetOverrides())->actual($root, 'phpunit10'));
    }

    public function testPbtExcludesOnlyExistingPhpUnitFiles(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-pbt-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/phpunit.xml.dist', '<phpunit/>');
        $rules = (new PresetOverrides())->pbtExcludes($root, ['pbt']);
        self::assertSame('phpunit.xml.dist', $rules[0]['file']);
        self::assertSame([], (new PresetOverrides())->pbtExcludes($root, ['composer']));
    }

    public function testRefileSkipsPresetsThatWereNotSelected(): void
    {
        self::assertSame([], (new PresetOverrides())->refile(['composer'], ['phpstan'], 'phpstan.neon.dist'));
    }

    public function testTestSuitesRequiresASuiteForEachExistingTestDirectory(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-suites-' . uniqid();
        mkdir($root . '/tests/Unit', 0777, true);
        mkdir($root . '/tests/Integration', 0777, true);
        file_put_contents($root . '/phpunit.xml.dist', '<phpunit/>');
        $rules = (new PresetOverrides())->testSuites($root, ['phpunit13', 'composer']);
        self::assertSame([
            [
                'id' => 'phpunit13.testsuite.unit',
                'message' => 'PHPUnit must run the tests in tests/Unit through a test suite named "unit": {select} in {file} must {expectation}. {fix}',
                'file' => 'phpunit.xml.dist',
                'format' => 'xml',
                'select' => '/phpunit/testsuites/testsuite[@name="unit"]/directory',
                'level' => 'required',
                'assert' => ['equals' => 'tests/Unit'],
            ],
            [
                'id' => 'phpunit13.testsuite.integration',
                'message' => 'PHPUnit must run the tests in tests/Integration through a test suite named "integration": {select} in {file} must {expectation}. {fix}',
                'file' => 'phpunit.xml.dist',
                'format' => 'xml',
                'select' => '/phpunit/testsuites/testsuite[@name="integration"]/directory',
                'level' => 'required',
                'assert' => ['equals' => 'tests/Integration'],
            ],
        ], $rules);
    }

    public function testTestSuitesSkipsMissingDirectoriesAndPointsAtTheDetectedFile(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-suites-unit-' . uniqid();
        mkdir($root . '/tests/Unit', 0777, true);
        file_put_contents($root . '/tests/Integration', 'a file, not a directory');
        file_put_contents($root . '/phpunit.xml', '<phpunit/>');
        $rules = (new PresetOverrides())->testSuites($root, ['phpunit10', 'doctest10']);
        self::assertCount(1, $rules);
        self::assertSame('phpunit10.testsuite.unit', $rules[0]['id']);
        self::assertSame('phpunit.xml', $rules[0]['file']);
        self::assertSame([], (new PresetOverrides())->testSuites($root, ['composer']));
    }

    public function testTestSuitesUsesThePresetDefaultWhenNoPhpUnitFileExists(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-suites-default-' . uniqid();
        mkdir($root . '/tests/Integration', 0777, true);
        $rules = (new PresetOverrides())->testSuites($root, ['phpunit9']);
        self::assertCount(1, $rules);
        self::assertSame('phpunit9.testsuite.integration', $rules[0]['id']);
        self::assertSame('phpunit9.xml.dist', $rules[0]['file']);
        self::assertSame([], (new PresetOverrides())->testSuites(sys_get_temp_dir() . '/guard-override-no-tests-' . uniqid(), ['phpunit9']));
    }

    /**
     * @throws JsonException
     */
    public function testApplyWritesTestSuiteRulesAfterTheFileOverrides(): void
    {
        $root = sys_get_temp_dir() . '/guard-override-apply-suites-' . uniqid();
        mkdir($root . '/tests/Unit', 0777, true);
        file_put_contents($root . '/phpunit.xml', '<phpunit/>');
        $document = (new PresetOverrides())->apply($root, ['phpunit12'], ['version' => 1]);
        $rules = $document['configuration'];
        self::assertIsArray($rules);
        $last = end($rules);
        self::assertIsArray($last);
        self::assertSame('phpunit12.testsuite.unit', $last['id']);
        self::assertSame('phpunit.xml', $last['file']);
        self::assertSame(['id' => 'phpunit12.beStrictAboutChangesToGlobalState', 'file' => 'phpunit.xml'], $rules[0]);
    }
}
