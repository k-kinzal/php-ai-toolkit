<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Loc\Policy;

use Guard\Config\Loc\ConfigKeyValidator;
use Guard\Config\Loc\ConfigScalarReader;
use Guard\Config\Loc\ConfigStringListReader;
use Guard\Config\Loc\LimitConfig;
use Guard\Config\Loc\Policy\ApplyConfig;
use Guard\Config\Loc\Policy\ApplyConfigReader;
use Guard\Config\Loc\Policy\ApplyPolicyUsageValidator;
use Guard\Config\Loc\Policy\ApplyRuleConfig;
use Guard\Config\Loc\Policy\ApplyRuleConfigReader;
use Guard\Config\Loc\Policy\ApplyRuleListConfigReader;
use Guard\Config\Loc\Policy\PolicyConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Loc\Policy\ApplyConfigReader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Loc\ConfigKeyValidator
 * @uses \Guard\Config\Loc\ConfigScalarReader
 * @uses \Guard\Config\Loc\ConfigStringListReader
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Config\Loc\Policy\ApplyConfig
 * @uses \Guard\Config\Loc\Policy\ApplyPolicyUsageValidator
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfig
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfigReader
 * @uses \Guard\Config\Loc\Policy\ApplyRuleListConfigReader
 * @uses \Guard\Config\Loc\Policy\PolicyConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ApplyConfigReader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(ApplyPolicyUsageValidator::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(ApplyRuleConfigReader::class)]
#[UsesClass(ApplyRuleListConfigReader::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class ApplyConfigReaderTest extends TestCase
{
    public function testReadValidatesAndReturnsPolicyAssignments(): void
    {
        $limits = LimitConfig::fromValues(['file.lines' => 500]);
        $policies = [
            'base' => new PolicyConfig('base', null, $limits),
            'standard' => new PolicyConfig('standard', 'base', $limits),
            'native-api' => new PolicyConfig('native-api', 'standard', $limits),
        ];
        $config = (new ApplyConfigReader())->read([
            'default' => 'standard',
            'rules' => [[
                'name' => 'native',
                'match' => ['paths' => ['src/Native*.php']],
                'policy' => 'native-api',
            ]],
        ], $policies);

        self::assertSame('standard', $config->defaultPolicy);
        self::assertSame('native-api', $config->rules[0]->policy);
    }

    public function testReadRejectsPolicyUnusedByAssignmentOrInheritance(): void
    {
        $limits = LimitConfig::fromValues(['file.lines' => 500]);
        $policies = [
            'standard' => new PolicyConfig('standard', null, $limits),
            'orphan' => new PolicyConfig('orphan', null, $limits),
        ];

        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('unused policies: orphan');
        (new ApplyConfigReader())->read(['default' => 'standard'], $policies);
    }
}
