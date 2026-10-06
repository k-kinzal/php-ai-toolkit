<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy\Loc;

use Guard\Config\Loc\ConfigKeyValidator;
use Guard\Config\Loc\ConfigScalarReader;
use Guard\Config\Loc\ConfigStringListReader;
use Guard\Config\Loc\LimitConfig;
use Guard\Config\Loc\LimitConfigReader;
use Guard\Config\Loc\MetricsConfig;
use Guard\Config\Loc\Policy\ApplyConfig;
use Guard\Config\Loc\Policy\ApplyConfigReader;
use Guard\Config\Loc\Policy\ApplyPolicyUsageValidator;
use Guard\Config\Loc\Policy\ApplyRuleConfig;
use Guard\Config\Loc\Policy\ApplyRuleConfigReader;
use Guard\Config\Loc\Policy\ApplyRuleListConfigReader;
use Guard\Config\Loc\Policy\PolicyConfig;
use Guard\Config\Loc\Policy\PolicyConfigReader;
use Guard\Config\Loc\Policy\PolicyDefinition;
use Guard\Config\Loc\Policy\PolicyListConfigReader;
use Guard\Config\Loc\Policy\PolicyResolver;
use Guard\Config\Loc\ScanConfig;
use Guard\Config\Loc\ScanConfigReader;
use Guard\Init\Legacy\Loc\ConfigLoader;
use Guard\Init\Legacy\Loc\ReportConfig;
use Guard\Init\Legacy\Loc\ReportConfigReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\Loc\ConfigLoader
 * @uses \Guard\Config\Configuration
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
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Init\Legacy\Loc\ReportConfig
 * @uses \Guard\Init\Legacy\Loc\ReportConfigReader
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ConfigLoader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(MetricsConfig::class)]
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
#[UsesClass(ScanConfig::class)]
#[UsesClass(ScanConfigReader::class)]
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
    public function testLoadsPoliciesAndPathAssignments(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
scan:
  roots: [src]
  exclude: ['src/Generated/**']
policies:
  standard:
    limits:
      file: { lines: 500, ncloc: 350 }
      function: { lines: 50, cyclomatic_complexity: 20 }
      method: { lines: 50, cyclomatic_complexity: 20 }
  native-api:
    extends: standard
    limits:
      file: { lines: 900 }
      function: { cyclomatic_complexity: null }
apply:
  default: standard
  rules:
    - name: native-api
      match:
        paths: ['src/Native*.php']
      policy: native-api
report:
  reporter: json
  order_by: [rule, path]
YAML);

        $config = (new ConfigLoader())->load($dir . '/loc.yaml');

        self::assertSame($dir, $config->root);
        self::assertSame(['src'], $config->scan->roots);
        self::assertSame(['src/Generated/**'], $config->scan->exclude);
        self::assertSame(500, $config->policies['standard']->limits->maxFileLines);
        self::assertSame(900, $config->policies['native-api']->limits->maxFileLines);
        self::assertSame(50, $config->policies['native-api']->limits->maxFunctionLines);
        self::assertNull($config->policies['native-api']->limits->maxFunctionCyclomaticComplexity);
        self::assertSame('standard', $config->apply->defaultPolicy);
        self::assertSame('native-api', $config->apply->rules[0]->name);
    }

    public function testLoadRejectsMissingConfig(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('config not found');

        (new ConfigLoader())->load(sys_get_temp_dir() . '/missing-locguard-' . uniqid('', true) . '.yaml');
    }

    public function testLoadRejectsMalformedYaml(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', "scan: [\n");

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid loc.yaml');

        (new ConfigLoader())->load($dir . '/loc.yaml');
    }

    public function testLoadRejectsUnknownLegacyTopLevelKey(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', "paths: [src]\n");

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('unsupported key "paths"');

        (new ConfigLoader())->load($dir . '/loc.yaml');
    }
}
