<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config\Policy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\ConfigKeyValidator;
use Toolkit\LocGuard\Config\ConfigScalarReader;
use Toolkit\LocGuard\Config\ConfigStringListReader;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\Policy\ApplyConfig;
use Toolkit\LocGuard\Config\Policy\ApplyConfigReader;
use Toolkit\LocGuard\Config\Policy\ApplyPolicyUsageValidator;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfig;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfigReader;
use Toolkit\LocGuard\Config\Policy\ApplyRuleListConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;

/**
 * @covers \Toolkit\LocGuard\Config\Policy\ApplyConfigReader
 */
#[CoversClass(ApplyConfigReader::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(ApplyPolicyUsageValidator::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(ApplyRuleConfigReader::class)]
#[UsesClass(ApplyRuleListConfigReader::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(PolicyConfig::class)]
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

        $this->expectException(\Toolkit\LocGuard\LocGuardException::class);
        $this->expectExceptionMessage('unused policies: orphan');
        (new ApplyConfigReader())->read(['default' => 'standard'], $policies);
    }
}
