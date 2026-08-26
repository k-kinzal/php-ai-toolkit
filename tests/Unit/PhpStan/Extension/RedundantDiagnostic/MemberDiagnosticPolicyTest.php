<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Extension\RedundantDiagnostic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Extension\RedundantDiagnostic\MemberDiagnosticPolicy;

/**
 * @covers \Toolkit\PhpStan\Extension\RedundantDiagnostic\MemberDiagnosticPolicy
 */
#[CoversClass(MemberDiagnosticPolicy::class)]
final class MemberDiagnosticPolicyTest extends TestCase
{
    public function testIsRedundantFollowsTheBroaderAndTestSpecificMethodRules(): void
    {
        $policy = new MemberDiagnosticPolicy();

        self::assertTrue($policy->isRedundant('method.unused', false, true, false, false, false));
        self::assertTrue($policy->isRedundant('method.finalPrivate', true, false, true, false, false));
        self::assertTrue($policy->isRedundant('consistentConstructor.private', true, true, true, false, false));
        self::assertFalse($policy->isRedundant('method.unused', false, false, true, false, false));
    }

    public function testTestMemberDiagnosticsAreScopedToTheirDeclarationRules(): void
    {
        $policy = new MemberDiagnosticPolicy();

        self::assertTrue($policy->isRedundant('property.onlyWritten', true, false, false, true, false));
        self::assertTrue($policy->isRedundant('classConstant.unused', true, false, false, false, true));
        self::assertFalse($policy->isRedundant('property.onlyWritten', false, false, false, true, false));
        self::assertFalse($policy->isRedundant('classConstant.unused', true, false, false, false, false));
    }

    public function testUnrelatedAndUnidentifiedDiagnosticsRemainVisible(): void
    {
        $policy = new MemberDiagnosticPolicy();

        self::assertFalse($policy->isRedundant('return.type', true, true, true, true, true));
        self::assertFalse($policy->isRedundant(null, true, true, true, true, true));
    }
}
