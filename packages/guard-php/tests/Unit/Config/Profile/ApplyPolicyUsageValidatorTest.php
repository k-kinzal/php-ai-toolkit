<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Profile;

use Guard\Config\Profile\ApplyPolicyUsageValidator;
use Guard\Config\Profile\ApplyRuleConfig;
use Guard\Config\Profile\PolicyConfig;
use Guard\Config\Value\LimitConfig;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Profile\ApplyPolicyUsageValidator
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Profile\ApplyRuleConfig
 * @uses \Guard\Config\Profile\PolicyConfig
 * @uses \Guard\Config\Value\LimitConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ApplyPolicyUsageValidator::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
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
