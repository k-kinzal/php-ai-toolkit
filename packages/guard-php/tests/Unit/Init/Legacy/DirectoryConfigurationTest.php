<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy;

use Guard\Config\Reader\DirectoryRuleConfigReader;
use Guard\Config\Reader\DirectoryRuleListConfigReader;
use Guard\Config\Validation\DirectoryConfigScalarReader;
use Guard\Config\Validation\DirectoryConfigStringListReader;
use Guard\Config\Value\DirectoryRuleConfig;
use Guard\Config\Value\StructureConfig;
use Guard\Init\Legacy\DirectoryConfiguration;
use Guard\Init\Legacy\DirectoryReportConfig;
use Guard\Init\Legacy\DirectoryReportReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\DirectoryConfiguration
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Reader\DirectoryRuleConfigReader
 * @uses \Guard\Config\Reader\DirectoryRuleListConfigReader
 * @uses \Guard\Config\Validation\DirectoryConfigScalarReader
 * @uses \Guard\Config\Validation\DirectoryConfigStringListReader
 * @uses \Guard\Config\Value\DirectoryRuleConfig
 * @uses \Guard\Config\Value\StructureConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Init\Legacy\DirectoryReportConfig
 * @uses \Guard\Init\Legacy\DirectoryReportReader
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(DirectoryConfiguration::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(DirectoryRuleConfigReader::class)]
#[UsesClass(DirectoryRuleListConfigReader::class)]
#[UsesClass(DirectoryConfigScalarReader::class)]
#[UsesClass(DirectoryConfigStringListReader::class)]
#[UsesClass(DirectoryRuleConfig::class)]
#[UsesClass(StructureConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(DirectoryReportConfig::class)]
#[UsesClass(DirectoryReportReader::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
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
