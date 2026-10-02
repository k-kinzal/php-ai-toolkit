<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Init;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Init\Initializer
 * @uses \Toolkit\DocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\DocGuard\Config\ConfigLoader
 * @uses \Toolkit\DocGuard\Config\ConfigStringListReader
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Config\DeclaredHeadingReader
 * @uses \Toolkit\DocGuard\Config\DocGuardConfig
 * @uses \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\Config\DocumentConfigReader
 * @uses \Toolkit\DocGuard\Config\DocumentListConfigReader
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 * @uses \Toolkit\DocGuard\Config\ReportConfigReader
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Filesystem\DocGuardPathResolver
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Markdown\BlockLineScanner
 * @uses \Toolkit\DocGuard\Markdown\BlockMarkerMatcher
 * @uses \Toolkit\DocGuard\Markdown\Fence
 * @uses \Toolkit\DocGuard\Markdown\FenceMatcher
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Markdown\HeadingParser
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 * @uses \Toolkit\DocGuard\Markdown\HtmlBlockMatcher
 * @uses \Toolkit\DocGuard\Markdown\LineIndentation
 * @uses \Toolkit\DocGuard\Markdown\LineScanner
 * @uses \Toolkit\DocGuard\Markdown\MarkdownLineSplitter
 * @uses \Toolkit\DocGuard\Markdown\ParserState
 * @uses \Toolkit\DocGuard\Markdown\SetextUnderlineMatcher
 * @uses \Toolkit\Guard\Config\Configuration
 * @uses \Toolkit\Guard\Config\ConfigurationLoader
 * @uses \Toolkit\Guard\Config\DocumentationReader
 * @uses \Toolkit\Guard\Config\QualityReader
 * @uses \Toolkit\Guard\Config\RuleReader
 * @uses \Toolkit\Guard\Config\Schema
 * @uses \Toolkit\Guard\Config\StructureReader
 * @uses \Toolkit\Guard\Document\Selection
 * @uses \Toolkit\Guard\Init\LegacyMigration
 * @uses \Toolkit\Guard\Init\Recommendations
 * @uses \Toolkit\Guard\Init\ToolDetector
 * @uses \Toolkit\Guard\Policy\Constraint
 * @uses \Toolkit\Guard\Policy\PolicyException
 * @uses \Toolkit\Guard\Policy\Rule
 * @uses \Toolkit\LocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\LocGuard\Config\ConfigLoader
 * @uses \Toolkit\LocGuard\Config\ConfigScalarReader
 * @uses \Toolkit\LocGuard\Config\ConfigStringListReader
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LimitConfigReader
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyPolicyUsageValidator
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleListConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyDefinition
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyListConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyResolver
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 * @uses \Toolkit\LocGuard\Config\ReportConfigReader
 * @uses \Toolkit\LocGuard\Config\ScanConfig
 * @uses \Toolkit\LocGuard\Config\ScanConfigReader
 * @uses \Toolkit\LocGuard\LocGuardException
 * @uses \Toolkit\TreeGuard\Config\ConfigLoader
 * @uses \Toolkit\TreeGuard\Config\ConfigScalarReader
 * @uses \Toolkit\TreeGuard\Config\ConfigStringListReader
 * @uses \Toolkit\TreeGuard\Config\ReportConfig
 * @uses \Toolkit\TreeGuard\Config\ReportConfigReader
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 * @uses \Toolkit\TreeGuard\Config\RuleConfigReader
 * @uses \Toolkit\TreeGuard\Config\RuleListConfigReader
 * @uses \Toolkit\TreeGuard\Config\TreeGuardConfig
 * @uses \Toolkit\TreeGuard\TreeGuardException
 */
#[CoversClass(\Toolkit\Guard\Init\Initializer::class)]
#[UsesClass(\Toolkit\DocGuard\Config\ConfigKeyValidator::class)]
#[UsesClass(\Toolkit\DocGuard\Config\ConfigLoader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\ConfigStringListReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DeclaredHeading::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DeclaredHeadingReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocGuardConfig::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocumentConfig::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocumentConfigReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\DocumentListConfigReader::class)]
#[UsesClass(\Toolkit\DocGuard\Config\ReportConfig::class)]
#[UsesClass(\Toolkit\DocGuard\Config\ReportConfigReader::class)]
#[UsesClass(\Toolkit\DocGuard\DocGuardException::class)]
#[UsesClass(\Toolkit\DocGuard\Filesystem\DocGuardPathResolver::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\AtxHeadingMatcher::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\BlockLineScanner::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\BlockMarkerMatcher::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\Fence::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\FenceMatcher::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\Heading::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\HeadingParser::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\HeadingTextNormalizer::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\HtmlBlockMatcher::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\LineIndentation::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\LineScanner::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\MarkdownLineSplitter::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\ParserState::class)]
#[UsesClass(\Toolkit\DocGuard\Markdown\SetextUnderlineMatcher::class)]
#[UsesClass(\Toolkit\Guard\Config\Configuration::class)]
#[UsesClass(\Toolkit\Guard\Config\ConfigurationLoader::class)]
#[UsesClass(\Toolkit\Guard\Config\DocumentationReader::class)]
#[UsesClass(\Toolkit\Guard\Config\QualityReader::class)]
#[UsesClass(\Toolkit\Guard\Config\RuleReader::class)]
#[UsesClass(\Toolkit\Guard\Config\Schema::class)]
#[UsesClass(\Toolkit\Guard\Config\StructureReader::class)]
#[UsesClass(\Toolkit\Guard\Document\Selection::class)]
#[UsesClass(\Toolkit\Guard\Init\LegacyMigration::class)]
#[UsesClass(\Toolkit\Guard\Init\Recommendations::class)]
#[UsesClass(\Toolkit\Guard\Init\ToolDetector::class)]
#[UsesClass(\Toolkit\Guard\Policy\Constraint::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
#[UsesClass(\Toolkit\Guard\Policy\Rule::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ConfigKeyValidator::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ConfigLoader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ConfigScalarReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ConfigStringListReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\LimitConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\LimitConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\LocGuardConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyPolicyUsageValidator::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyRuleConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyRuleConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\ApplyRuleListConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyDefinition::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyListConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\Policy\PolicyResolver::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ReportConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ReportConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ScanConfig::class)]
#[UsesClass(\Toolkit\LocGuard\Config\ScanConfigReader::class)]
#[UsesClass(\Toolkit\LocGuard\LocGuardException::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ConfigLoader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ConfigScalarReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ConfigStringListReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ReportConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\ReportConfigReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\RuleConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\RuleConfigReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\RuleListConfigReader::class)]
#[UsesClass(\Toolkit\TreeGuard\Config\TreeGuardConfig::class)]
#[UsesClass(\Toolkit\TreeGuard\TreeGuardException::class)]
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
        (new \Toolkit\Guard\Init\Initializer())->write($root . '/guard.yaml');
        $config = (new \Toolkit\Guard\Config\ConfigurationLoader())->load($root . '/guard.yaml');
        self::assertSame('recommended', $config->rules[0]->level);
        self::assertSame('{"config":{"sort-packages":false}}', file_get_contents($root . '/composer.json'));
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Init\Initializer())->write($root . '/guard.yaml');
    }

    /**
     * @throws JsonException
     */
    public function testConfigurationAvoidsAbsentTools(): void
    {
        $root = sys_get_temp_dir() . '/guard-empty-' . uniqid();
        mkdir($root);
        $config = (new \Toolkit\Guard\Init\Initializer())->configuration($root);
        self::assertSame([], $config['configuration']);
        self::assertArrayNotHasKey('quality', $config);
    }
    public function testLimitsPreservesTheCurrentStrictProfile(): void
    {
        $limits = (new \Toolkit\Guard\Init\Initializer())->limits();
        self::assertSame(['lines' => 500, 'ncloc' => 350], $limits['file']);
        self::assertSame(['lines' => 50, 'cyclomatic_complexity' => 20], $limits['method']);
    }
    /**
     * @throws JsonException
     */
    public function testStructureRetainsNamingAndDensityConstraints(): void
    {
        $structure = (new \Toolkit\Guard\Init\Initializer())->structure(['src']);
        self::assertSame(['.'], $structure['paths']);
        self::assertStringContainsString('*Helper.php', json_encode($structure, JSON_THROW_ON_ERROR));
        self::assertStringContainsString('"max_files":15', json_encode($structure, JSON_THROW_ON_ERROR));
    }
}
