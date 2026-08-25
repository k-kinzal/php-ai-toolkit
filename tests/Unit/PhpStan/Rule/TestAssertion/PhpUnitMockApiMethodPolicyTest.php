<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestAssertion;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\TestAssertion\PhpUnitMockApiMethodPolicy;

/**
 * @covers \Toolkit\PhpStan\Rule\TestAssertion\PhpUnitMockApiMethodPolicy
 */
#[CoversClass(PhpUnitMockApiMethodPolicy::class)]
final class PhpUnitMockApiMethodPolicyTest extends TestCase
{
    public function testIsAlwaysProhibitedReturnsTrueForMockBuilder(): void
    {
        self::assertTrue((new PhpUnitMockApiMethodPolicy())->isAlwaysProhibited('getMockBuilder'));
    }

    public function testRequiresInterfaceTargetReturnsTrueForCreateMock(): void
    {
        self::assertTrue((new PhpUnitMockApiMethodPolicy())->requiresInterfaceTarget('createMock'));
    }
}
