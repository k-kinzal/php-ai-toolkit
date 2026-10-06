<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Loc\Policy;

use Guard\Config\Loc\LimitConfig;
use Guard\Config\Loc\Policy\ApplyPolicyUsageValidator;
use Guard\Config\Loc\Policy\ApplyRuleConfig;
use Guard\Config\Loc\Policy\PolicyConfig;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Loc\Policy\ApplyPolicyUsageValidator
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Config\Loc\Policy\ApplyRuleConfig
 * @uses \Guard\Config\Loc\Policy\PolicyConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ApplyPolicyUsageValidator::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class ApplyPolicyUsageValidatorTest extends TestCase
{
    public function testValidateCountsInheritedPoliciesAsUsed(): void
    {
        $limits = LimitConfig::fromValues(['file.lines' => 500]);
        $policies = [
            'base' => new PolicyConfig('base', null, $limits),
            'standard' => new PolicyConfig('standard', 'base', $limits),
        ];

        (new ApplyPolicyUsageValidator())->validate('standard', [], $policies);
        $this->addToAssertionCount(1);
    }

    public function testValidateRejectsUnknownPolicy(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('apply references unknown policy "missing"');
        (new ApplyPolicyUsageValidator())->validate('missing', [], []);
    }

    public function testValidateRejectsUnusedPolicy(): void
    {
        $limits = LimitConfig::fromValues(['file.lines' => 500]);
        $policies = [
            'standard' => new PolicyConfig('standard', null, $limits),
            'orphan' => new PolicyConfig('orphan', null, $limits),
        ];

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('unused policies: orphan');
        (new ApplyPolicyUsageValidator())->validate('standard', [], $policies);
    }
}
