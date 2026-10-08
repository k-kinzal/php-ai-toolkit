<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Profile;

use Guard\Config\Profile\ApplyPolicyUsageValidator;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Definition\ApplyRuleConfig;
use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Definition\PolicyConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Profile\ApplyPolicyUsageValidator
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Policy\Definition\ApplyRuleConfig
 * @uses \Guard\Policy\Definition\PolicyConfig
 * @uses \Guard\Policy\Definition\LimitConfig
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(ApplyPolicyUsageValidator::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
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
