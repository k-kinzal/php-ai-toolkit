<?php

declare(strict_types=1);

namespace Tests\Unit\Init;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Initializer
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
 * @uses \Guard\Config\ConfigurationLoader
 * @uses \Guard\Config\Doc\ConfigKeyValidator
 * @uses \Guard\Config\Doc\ConfigStringListReader
 * @uses \Guard\Config\Doc\DeclaredHeading
 * @uses \Guard\Config\Doc\DeclaredHeadingReader
 * @uses \Guard\Config\Doc\DocumentConfig
 * @uses \Guard\Config\Doc\DocumentConfigReader
 * @uses \Guard\Config\Doc\DocumentListConfigReader
 * @uses \Guard\Config\Doc\DocumentationConfig
 * @uses \Guard\Config\DocumentMerger
 * @uses \Guard\Config\DocumentationReader
 * @uses \Guard\Config\ImportResolver
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
 * @uses \Guard\Config\MetricsReader
 * @uses \Guard\Config\RuleReader
 * @uses \Guard\Config\Schema
 * @uses \Guard\Config\StructureReader
 * @uses \Guard\Config\Tree\ConfigScalarReader
 * @uses \Guard\Config\Tree\ConfigStringListReader
 * @uses \Guard\Config\Tree\RuleConfig
 * @uses \Guard\Config\Tree\RuleConfigReader
 * @uses \Guard\Config\Tree\RuleListConfigReader
 * @uses \Guard\Config\Tree\StructureConfig
 * @uses \Guard\Document\Selection
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
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
 * @uses \Guard\Init\PresetSelector
 * @uses \Guard\Init\Recommendations
 * @uses \Guard\Init\ToolDetector
 * @uses \Guard\Policy\Constraint
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Init\Initializer::class)]
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
#[UsesClass(\Guard\Config\ConfigurationLoader::class)]
#[UsesClass(\Guard\Config\Doc\ConfigKeyValidator::class)]
#[UsesClass(\Guard\Config\Doc\ConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Doc\DeclaredHeading::class)]
#[UsesClass(\Guard\Config\Doc\DeclaredHeadingReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentConfig::class)]
#[UsesClass(\Guard\Config\Doc\DocumentConfigReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentListConfigReader::class)]
#[UsesClass(\Guard\Config\Doc\DocumentationConfig::class)]
#[UsesClass(\Guard\Config\DocumentMerger::class)]
#[UsesClass(\Guard\Config\DocumentationReader::class)]
#[UsesClass(\Guard\Config\ImportResolver::class)]
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
#[UsesClass(\Guard\Config\MetricsReader::class)]
#[UsesClass(\Guard\Config\RuleReader::class)]
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Config\StructureReader::class)]
#[UsesClass(\Guard\Config\Tree\ConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Tree\ConfigStringListReader::class)]
#[UsesClass(\Guard\Config\Tree\RuleConfig::class)]
#[UsesClass(\Guard\Config\Tree\RuleConfigReader::class)]
#[UsesClass(\Guard\Config\Tree\RuleListConfigReader::class)]
#[UsesClass(\Guard\Config\Tree\StructureConfig::class)]
#[UsesClass(\Guard\Document\Selection::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
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
#[UsesClass(\Guard\Init\PresetSelector::class)]
#[UsesClass(\Guard\Init\Recommendations::class)]
#[UsesClass(\Guard\Init\ToolDetector::class)]
#[UsesClass(\Guard\Policy\Constraint::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class InitializerTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testWriteCreatesRecommendedPolicyWithoutOverwritingExistingFiles(): void
    {
        $root = sys_get_temp_dir() . '/guard-init-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/composer.json', '{"config":{"sort-packages":false}}');
        (new \Guard\Init\Initializer())->write($root . '/guard.yaml');
        $config = (new \Guard\Config\ConfigurationLoader())->load($root . '/guard.yaml');
        self::assertSame('recommended', $config->rules[0]->level);
        self::assertSame('{"config":{"sort-packages":false}}', file_get_contents($root . '/composer.json'));
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Init\Initializer())->write($root . '/guard.yaml');
    }

    /**
     * @throws JsonException
     */
    public function testConfigurationAvoidsAbsentTools(): void
    {
        $root = sys_get_temp_dir() . '/guard-empty-' . uniqid();
        mkdir($root);
        $config = (new \Guard\Init\Initializer())->configuration($root);
        self::assertArrayNotHasKey('configuration', $config);
        self::assertArrayNotHasKey('imports', $config);
        self::assertArrayNotHasKey('metrics', $config);
        self::assertArrayNotHasKey('scope', $config);
    }

    /**
     * @throws JsonException
     */
    public function testConfigurationImportsOnlyRequestedPresets(): void
    {
        $root = sys_get_temp_dir() . '/guard-requested-' . uniqid();
        mkdir($root);
        $config = (new \Guard\Init\Initializer())->configuration($root, ['composer', 'composer']);
        self::assertStringContainsString('composer.yaml', json_encode($config['imports'], JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('metrics.yaml', json_encode($config['imports'], JSON_THROW_ON_ERROR));
    }

    /**
     * @throws JsonException
     */
    public function testConfigurationKeepsLegacyMetricsOutOfImports(): void
    {
        $root = sys_get_temp_dir() . '/guard-legacy-init-' . uniqid();
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/loc.yaml', "scan: {roots: [lib], exclude: []}\npolicies:\n  strict:\n    limits: {file: {lines: 99}}\napply: {default: strict, rules: []}\n");
        $config = (new \Guard\Init\Initializer())->configuration($root, ['metrics', 'structure']);
        self::assertStringNotContainsString('metrics.yaml', json_encode($config['imports'], JSON_THROW_ON_ERROR));
        self::assertSame(['source' => ['lib'], 'exclude' => [], 'profiles' => ['strict' => ['limits' => ['file' => ['lines' => 99]]]], 'default' => 'strict', 'assignments' => []], $config['metrics']);
    }

    /**
     * @throws JsonException
     */
    public function testConfigurationOverridesDetectedPhpStanFile(): void
    {
        $root = sys_get_temp_dir() . '/guard-phpstan-init-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/composer.json', '{"require-dev":{"phpstan/phpstan":"^2.0","k-kinzal/phpstan-guard-rules":"^1.0"}}');
        file_put_contents($root . '/phpstan.neon.dist', "parameters:\n    level: 5\n");
        $config = (new \Guard\Init\Initializer())->configuration($root);
        self::assertSame([
            ['id' => 'phpstan.level', 'file' => 'phpstan.neon.dist'],
            ['id' => 'phpstan.strict-rules', 'file' => 'phpstan.neon.dist'],
            ['id' => 'phpstan.rules', 'file' => 'phpstan.neon.dist'],
            ['id' => 'phpstan.extension', 'file' => 'phpstan.neon.dist'],
            ['id' => 'phpstan.all-rules', 'file' => 'phpstan.neon.dist'],
        ], $config['configuration']);
    }

    public function testReadmeRecordsTheCurrentHeadings(): void
    {
        $root = sys_get_temp_dir() . '/guard-readme-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/README.md', "# Tool\n");
        self::assertSame(['files' => ['README.md' => ['headings' => ['# Tool']]], 'scan' => ['README.md']], (new \Guard\Init\Initializer())->readme($root));
        self::assertNull((new \Guard\Init\Initializer())->readme(sys_get_temp_dir() . '/guard-no-readme-' . uniqid()));
    }
    public function testLimitsPreservesTheCurrentStrictProfile(): void
    {
        $limits = (new \Guard\Init\Initializer())->limits();
        self::assertSame(['lines' => 500, 'ncloc' => 350], $limits['file']);
        self::assertSame(['lines' => 50, 'cyclomatic_complexity' => 20], $limits['method']);
    }
    /**
     * @throws JsonException
     */
    public function testStructureRetainsNamingAndDensityConstraints(): void
    {
        $structure = (new \Guard\Init\Initializer())->structure(['src']);
        self::assertSame(['.'], $structure['paths']);
        self::assertStringContainsString('*Helper.php', json_encode($structure, JSON_THROW_ON_ERROR));
        self::assertStringContainsString('"max_files":15', json_encode($structure, JSON_THROW_ON_ERROR));
    }
}
