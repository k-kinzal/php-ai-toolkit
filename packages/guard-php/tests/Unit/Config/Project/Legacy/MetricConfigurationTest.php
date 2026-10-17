<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project\Legacy;

use Guard\Config\Configuration;
use Guard\Config\Profile\ApplyConfigReader;
use Guard\Config\Profile\ApplyPolicyUsageValidator;
use Guard\Config\Profile\ApplyRuleConfigReader;
use Guard\Config\Profile\ApplyRuleListConfigReader;
use Guard\Config\Profile\PolicyConfigReader;
use Guard\Config\Profile\PolicyDefinition;
use Guard\Config\Profile\PolicyListConfigReader;
use Guard\Config\Profile\PolicyResolver;
use Guard\Config\Project\Legacy\MetricConfiguration;
use Guard\Config\Project\Legacy\MetricReportConfig;
use Guard\Config\Project\Legacy\MetricReportReader;
use Guard\Config\Reader\LimitConfigReader;
use Guard\Config\Reader\ScanConfigReader;
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
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Context;
use Guard\Policy\Definition\ApplyConfig;
use Guard\Policy\Definition\ApplyRuleConfig;
use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Definition\MetricsConfig;
use Guard\Policy\Definition\PolicyConfig;
use Guard\Policy\Definition\ScanConfig;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Project\Legacy\MetricConfiguration
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
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
 * @uses \Guard\Config\Reader\LimitConfigReader
 * @uses \Guard\Config\Reader\ScanConfigReader
 * @uses \Guard\Config\Validation\MetricConfigKeyValidator
 * @uses \Guard\Config\Validation\MetricConfigScalarReader
 * @uses \Guard\Config\Validation\MetricConfigStringListReader
 * @uses \Guard\Policy\Definition\LimitConfig
 * @uses \Guard\Policy\Definition\MetricsConfig
 * @uses \Guard\Policy\Definition\ScanConfig
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Config\Project\Legacy\MetricReportConfig
 * @uses \Guard\Config\Project\Legacy\MetricReportReader
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(MetricConfiguration::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
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
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(ScanConfigReader::class)]
#[UsesClass(MetricConfigKeyValidator::class)]
#[UsesClass(MetricConfigScalarReader::class)]
#[UsesClass(MetricConfigStringListReader::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(MetricsConfig::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(MetricReportConfig::class)]
#[UsesClass(MetricReportReader::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
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
