<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy;

use Guard\Config\Profile\ApplyConfig;
use Guard\Config\Profile\ApplyConfigReader;
use Guard\Config\Profile\ApplyPolicyUsageValidator;
use Guard\Config\Profile\ApplyRuleConfig;
use Guard\Config\Profile\ApplyRuleConfigReader;
use Guard\Config\Profile\ApplyRuleListConfigReader;
use Guard\Config\Profile\PolicyConfig;
use Guard\Config\Profile\PolicyConfigReader;
use Guard\Config\Profile\PolicyDefinition;
use Guard\Config\Profile\PolicyListConfigReader;
use Guard\Config\Profile\PolicyResolver;
use Guard\Config\Reader\LimitConfigReader;
use Guard\Config\Reader\ScanConfigReader;
use Guard\Config\Validation\MetricConfigKeyValidator;
use Guard\Config\Validation\MetricConfigScalarReader;
use Guard\Config\Validation\MetricConfigStringListReader;
use Guard\Config\Value\LimitConfig;
use Guard\Config\Value\MetricsConfig;
use Guard\Config\Value\ScanConfig;
use Guard\Init\Legacy\MetricConfiguration;
use Guard\Init\Legacy\MetricReportConfig;
use Guard\Init\Legacy\MetricReportReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\MetricConfiguration
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Profile\ApplyConfig
 * @uses \Guard\Config\Profile\ApplyConfigReader
 * @uses \Guard\Config\Profile\ApplyPolicyUsageValidator
 * @uses \Guard\Config\Profile\ApplyRuleConfig
 * @uses \Guard\Config\Profile\ApplyRuleConfigReader
 * @uses \Guard\Config\Profile\ApplyRuleListConfigReader
 * @uses \Guard\Config\Profile\PolicyConfig
 * @uses \Guard\Config\Profile\PolicyConfigReader
 * @uses \Guard\Config\Profile\PolicyDefinition
 * @uses \Guard\Config\Profile\PolicyListConfigReader
 * @uses \Guard\Config\Profile\PolicyResolver
 * @uses \Guard\Config\Reader\LimitConfigReader
 * @uses \Guard\Config\Reader\ScanConfigReader
 * @uses \Guard\Config\Validation\MetricConfigKeyValidator
 * @uses \Guard\Config\Validation\MetricConfigScalarReader
 * @uses \Guard\Config\Validation\MetricConfigStringListReader
 * @uses \Guard\Config\Value\LimitConfig
 * @uses \Guard\Config\Value\MetricsConfig
 * @uses \Guard\Config\Value\ScanConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Init\Legacy\MetricReportConfig
 * @uses \Guard\Init\Legacy\MetricReportReader
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(MetricConfiguration::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
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
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(ScanConfigReader::class)]
#[UsesClass(MetricConfigKeyValidator::class)]
#[UsesClass(MetricConfigScalarReader::class)]
#[UsesClass(MetricConfigStringListReader::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(MetricsConfig::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(MetricReportConfig::class)]
#[UsesClass(MetricReportReader::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class MetricConfigurationTest extends TestCase
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

        $config = (new MetricConfiguration())->load($dir . '/loc.yaml');

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

        (new MetricConfiguration())->load(sys_get_temp_dir() . '/missing-locguard-' . uniqid('', true) . '.yaml');
    }

    public function testLoadRejectsMalformedYaml(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', "scan: [\n");

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid loc.yaml');

        (new MetricConfiguration())->load($dir . '/loc.yaml');
    }

    public function testLoadRejectsUnknownLegacyTopLevelKey(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-config-' . uniqid('', true);
        mkdir($dir);
        file_put_contents($dir . '/loc.yaml', "paths: [src]\n");

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('unsupported key "paths"');

        (new MetricConfiguration())->load($dir . '/loc.yaml');
    }
}
