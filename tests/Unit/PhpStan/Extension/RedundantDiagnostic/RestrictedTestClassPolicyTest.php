<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Extension\RedundantDiagnostic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Extension\RedundantDiagnostic\RestrictedTestClassPolicy;

/**
 * @covers \Toolkit\PhpStan\Extension\RedundantDiagnostic\RestrictedTestClassPolicy
 */
#[CoversClass(RestrictedTestClassPolicy::class)]
final class RestrictedTestClassPolicyTest extends TestCase
{
    public function testIsRestrictedRequiresBothConfiguredNamespaceSets(): void
    {
        $policy = new RestrictedTestClassPolicy(
            ['Tests', 'Specs\\'],
            ['Tests\Unit', 'Specs\Integration'],
        );

        self::assertTrue($policy->isRestricted('Tests\Unit\ExampleTest'));
        self::assertTrue($policy->isRestricted('\Specs\Integration\ExampleTest'));
        self::assertFalse($policy->isRestricted('Tests\Fixture\ExampleTest'));
        self::assertFalse($policy->isRestricted('App\Unit\ExampleTest'));
        self::assertFalse($policy->isRestricted(null));
    }
}
