<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config\Policy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\Policy\ApplyPolicyUsageValidator;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\Config\Policy\ApplyPolicyUsageValidator
 */
#[CoversClass(ApplyPolicyUsageValidator::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(PolicyConfig::class)]
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
        $this->expectException(LocGuardException::class);
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

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('unused policies: orphan');
        (new ApplyPolicyUsageValidator())->validate('standard', [], $policies);
    }
}
