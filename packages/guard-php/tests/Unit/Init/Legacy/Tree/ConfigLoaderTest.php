<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy\Tree;

use Guard\Config\Tree\ConfigScalarReader;
use Guard\Config\Tree\ConfigStringListReader;
use Guard\Config\Tree\RuleConfig;
use Guard\Config\Tree\RuleConfigReader;
use Guard\Config\Tree\RuleListConfigReader;
use Guard\Config\Tree\StructureConfig;
use Guard\Init\Legacy\Tree\ConfigLoader;
use Guard\Init\Legacy\Tree\ReportConfig;
use Guard\Init\Legacy\Tree\ReportConfigReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\Tree\ConfigLoader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Tree\ConfigScalarReader
 * @uses \Guard\Config\Tree\ConfigStringListReader
 * @uses \Guard\Config\Tree\RuleConfig
 * @uses \Guard\Config\Tree\RuleConfigReader
 * @uses \Guard\Config\Tree\RuleListConfigReader
 * @uses \Guard\Config\Tree\StructureConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Init\Legacy\Tree\ReportConfig
 * @uses \Guard\Init\Legacy\Tree\ReportConfigReader
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ConfigLoader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(RuleConfigReader::class)]
#[UsesClass(RuleListConfigReader::class)]
#[UsesClass(StructureConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ReportConfigReader::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class ConfigLoaderTest extends TestCase
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

        $config = (new ConfigLoader())->load($dir . '/tree.yaml');

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

        $config = (new ConfigLoader())->load($dir . '/tree.yaml');

        self::assertSame(['src'], $config->paths);
        self::assertSame([], $config->exclude);
        self::assertSame([], $config->rules);
    }

    public function testLoadRejectsMissingConfig(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('config not found');

        (new ConfigLoader())->load(sys_get_temp_dir() . '/missing-treeguard-' . uniqid('', true) . '.yaml');
    }

    public function testLoadRejectsMalformedYaml(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/tree.yaml', "paths: [\n");

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml');

        (new ConfigLoader())->load($dir . '/tree.yaml');
    }

    public function testLoadRejectsScalarTopLevelYaml(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/tree.yaml', "42\n");

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('top-level value must be a mapping');

        (new ConfigLoader())->load($dir . '/tree.yaml');
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

        (new ConfigLoader())->load($dir . '/tree.yaml');
    }
}
