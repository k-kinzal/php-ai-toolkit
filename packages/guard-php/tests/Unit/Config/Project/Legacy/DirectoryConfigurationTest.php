<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project\Legacy;

use Guard\Config\Project\Legacy\DirectoryConfiguration;
use Guard\Config\Project\Legacy\DirectoryReportConfig;
use Guard\Config\Project\Legacy\DirectoryReportReader;
use Guard\Config\Reader\DirectoryRuleConfigReader;
use Guard\Config\Reader\DirectoryRuleListConfigReader;
use Guard\Config\Validation\DirectoryConfigScalarReader;
use Guard\Config\Validation\DirectoryConfigStringListReader;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Definition\DirectoryRuleConfig;
use Guard\Policy\Definition\StructureConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Project\Legacy\DirectoryConfiguration
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Reader\DirectoryRuleConfigReader
 * @uses \Guard\Config\Reader\DirectoryRuleListConfigReader
 * @uses \Guard\Config\Validation\DirectoryConfigScalarReader
 * @uses \Guard\Config\Validation\DirectoryConfigStringListReader
 * @uses \Guard\Policy\Definition\DirectoryRuleConfig
 * @uses \Guard\Policy\Definition\StructureConfig
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Config\Project\Legacy\DirectoryReportConfig
 * @uses \Guard\Config\Project\Legacy\DirectoryReportReader
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(DirectoryConfiguration::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(DirectoryRuleConfigReader::class)]
#[UsesClass(DirectoryRuleListConfigReader::class)]
#[UsesClass(DirectoryConfigScalarReader::class)]
#[UsesClass(DirectoryConfigStringListReader::class)]
#[UsesClass(DirectoryRuleConfig::class)]
#[UsesClass(StructureConfig::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(DirectoryReportConfig::class)]
#[UsesClass(DirectoryReportReader::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
final class DirectoryConfigurationTest extends TestCase
{
    public function testLoadParsesTreeYaml(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/tree.yaml', <<<'YAML'
paths:
  - src
  - skills
exclude:
  - 'src/Generated/*'
rules:
  - path: 'src/**'
    max_files: 25
    allow: ['*.php']
    file_case: pascal
  - path: 'skills/*'
    require: ['SKILL.md']
    forbid_empty: true
report:
  reporter: json
  order_by:
    - rule
    - path
YAML);

        $config = (new DirectoryConfiguration())->load($dir . '/tree.yaml');

        self::assertSame($dir, $config->root);
        self::assertSame(['src', 'skills'], $config->paths);
        self::assertSame(['src/Generated/*'], $config->exclude);
        self::assertCount(2, $config->rules);
        self::assertSame('src/**', $config->rules[0]->path);
        self::assertSame(25, $config->rules[0]->maxFiles);
        self::assertSame(['*.php'], $config->rules[0]->allow);
        self::assertSame('pascal', $config->rules[0]->fileCase);
        self::assertSame('skills/*', $config->rules[1]->path);
        self::assertSame(['SKILL.md'], $config->rules[1]->require);
        self::assertTrue($config->rules[1]->forbidEmpty);
    }

    public function testLoadAppliesDefaults(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/tree.yaml', <<<'YAML'
exclude: []
YAML);

        $config = (new DirectoryConfiguration())->load($dir . '/tree.yaml');

        self::assertSame(['src'], $config->paths);
        self::assertSame([], $config->exclude);
        self::assertSame([], $config->rules);
    }

    public function testLoadRejectsMissingConfig(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('config not found');

        (new DirectoryConfiguration())->load(sys_get_temp_dir() . '/missing-treeguard-' . uniqid('', true) . '.yaml');
    }

    public function testLoadRejectsMalformedYaml(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/tree.yaml', "paths: [\n");

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml');

        (new DirectoryConfiguration())->load($dir . '/tree.yaml');
    }

    public function testLoadRejectsScalarTopLevelYaml(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/tree.yaml', "42\n");

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('top-level value must be a mapping');

        (new DirectoryConfiguration())->load($dir . '/tree.yaml');
    }

    public function testLoadRejectsUnknownRuleKey(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/tree.yaml', <<<'YAML'
rules:
  - path: src
    max_file: 25
YAML);

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"rules[0]" contains unsupported key "max_file"');

        (new DirectoryConfiguration())->load($dir . '/tree.yaml');
    }
}
