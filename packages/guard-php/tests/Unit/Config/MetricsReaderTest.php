<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\MetricsReader
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
 * @uses \Guard\Config\Schema
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Config\MetricsReader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
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
#[UsesClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class MetricsReaderTest extends TestCase
{
    public function testReadPreservesConfiguredMetricThresholds(): void
    {
        $config = (new \Guard\Config\MetricsReader())->read(['source' => ['lib'], 'profiles' => ['standard' => ['limits' => ['file' => ['lines' => 500]]]], 'default' => 'standard'], '/project');
        self::assertSame(500, $config->policies['standard']->limits->maxFileLines);
        self::assertSame(['lib'], $config->scan->roots);
    }

}
