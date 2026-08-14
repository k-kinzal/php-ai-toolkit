<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Config;

use PhpAiToolkit\TreeGuard\Config\ConfigLoader;
use PhpAiToolkit\TreeGuard\Config\ConfigScalarReader;
use PhpAiToolkit\TreeGuard\Config\ConfigStringListReader;
use PhpAiToolkit\TreeGuard\Config\ReportConfig;
use PhpAiToolkit\TreeGuard\Config\ReportConfigReader;
use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Config\RuleConfigReader;
use PhpAiToolkit\TreeGuard\Config\RuleListConfigReader;
use PhpAiToolkit\TreeGuard\Config\TreeGuardConfig;
use PhpAiToolkit\TreeGuard\TreeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigLoader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ReportConfigReader::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(RuleConfigReader::class)]
#[UsesClass(RuleListConfigReader::class)]
#[UsesClass(TreeGuardConfig::class)]
#[UsesClass(TreeGuardException::class)]
final class ConfigLoaderTest extends TestCase
{
    public function testLoadParsesTreeYaml(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-config-' . bin2hex(random_bytes(4));
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
        self::assertSame('json', $config->report->reporter);
        self::assertSame(['rule', 'path'], $config->report->orderBy);
    }

    public function testLoadAppliesDefaults(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/tree.yaml', <<<'YAML'
exclude: []
YAML);

        $config = (new ConfigLoader())->load($dir . '/tree.yaml');

        self::assertSame(['src'], $config->paths);
        self::assertSame([], $config->exclude);
        self::assertSame([], $config->rules);
        self::assertSame('ai', $config->report->reporter);
        self::assertSame(['path', 'rule'], $config->report->orderBy);
    }

    public function testLoadRejectsMissingConfig(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('config not found');

        (new ConfigLoader())->load(sys_get_temp_dir() . '/missing-treeguard-' . bin2hex(random_bytes(4)) . '.yaml');
    }

    public function testLoadRejectsMalformedYaml(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/tree.yaml', "paths: [\n");

        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml');

        (new ConfigLoader())->load($dir . '/tree.yaml');
    }

    public function testLoadRejectsScalarTopLevelYaml(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/tree.yaml', "42\n");

        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('top-level value must be a mapping');

        (new ConfigLoader())->load($dir . '/tree.yaml');
    }

    public function testLoadRejectsUnknownRuleKey(): void
    {
        $dir = sys_get_temp_dir() . '/treeguard-config-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents($dir . '/tree.yaml', <<<'YAML'
rules:
  - path: src
    max_file: 25
YAML);

        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('"rules[0]" contains unsupported key "max_file"');

        (new ConfigLoader())->load($dir . '/tree.yaml');
    }
}
