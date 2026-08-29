<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\ConfigKeyValidator;
use Toolkit\LocGuard\Config\ConfigLoader;
use Toolkit\LocGuard\Config\ConfigScalarReader;
use Toolkit\LocGuard\Config\ConfigStringListReader;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\LimitConfigReader;
use Toolkit\LocGuard\Config\LocGuardConfig;
use Toolkit\LocGuard\Config\Policy\ApplyConfig;
use Toolkit\LocGuard\Config\Policy\ApplyConfigReader;
use Toolkit\LocGuard\Config\Policy\ApplyPolicyUsageValidator;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfig;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfigReader;
use Toolkit\LocGuard\Config\Policy\ApplyRuleListConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyDefinition;
use Toolkit\LocGuard\Config\Policy\PolicyListConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyResolver;
use Toolkit\LocGuard\Config\ReportConfig;
use Toolkit\LocGuard\Config\ReportConfigReader;
use Toolkit\LocGuard\Config\ScanConfig;
use Toolkit\LocGuard\Config\ScanConfigReader;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\Config\ConfigLoader
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyPolicyUsageValidator
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleConfig
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\ApplyRuleListConfigReader
 * @uses \Toolkit\LocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\LocGuard\Config\ConfigScalarReader
 * @uses \Toolkit\LocGuard\Config\ConfigStringListReader
 * @uses \Toolkit\LocGuard\Config\LimitConfig
 * @uses \Toolkit\LocGuard\Config\LimitConfigReader
 * @uses \Toolkit\LocGuard\Config\LocGuardConfig
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfig
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyDefinition
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyListConfigReader
 * @uses \Toolkit\LocGuard\Config\Policy\PolicyResolver
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 * @uses \Toolkit\LocGuard\Config\ReportConfigReader
 * @uses \Toolkit\LocGuard\Config\ScanConfig
 * @uses \Toolkit\LocGuard\Config\ScanConfigReader
 */
#[CoversClass(ConfigLoader::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(ApplyConfigReader::class)]
#[UsesClass(ApplyPolicyUsageValidator::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(ApplyRuleConfigReader::class)]
#[UsesClass(ApplyRuleListConfigReader::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(PolicyConfigReader::class)]
#[UsesClass(PolicyDefinition::class)]
#[UsesClass(PolicyListConfigReader::class)]
#[UsesClass(PolicyResolver::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ReportConfigReader::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(ScanConfigReader::class)]
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
        self::assertSame('json', $config->report->reporter);
    }

    public function testLoadRejectsMissingConfig(): void
    {
        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('config not found');

        (new ConfigLoader())->load(sys_get_temp_dir() . '/missing-locguard-' . uniqid('', true) . '.yaml');
    }

    public function testLoadRejectsMalformedYaml(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', "scan: [\n");

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('Invalid loc.yaml');

        (new ConfigLoader())->load($dir . '/loc.yaml');
    }

    public function testLoadRejectsUnknownLegacyTopLevelKey(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', "paths: [src]\n");

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('unsupported key "paths"');

        (new ConfigLoader())->load($dir . '/loc.yaml');
    }
}
