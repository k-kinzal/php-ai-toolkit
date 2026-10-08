<?php

declare(strict_types=1);

namespace Tests\Unit\Input;

use Guard\Input\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Input\Scope
 */
#[CoversClass(Scope::class)]
final class ScopeTest extends TestCase
{
    public function testScopeRetainsTheSharedBoundaryIndependentlyOfPolicies(): void
    {
        $scope = new Scope(['src', 'docs/**/*.md'], ['src/generated']);
        self::assertSame(['src', 'docs/**/*.md'], $scope->include);
        self::assertSame(['src/generated'], $scope->exclude);
        self::assertSame(['**'], (new Scope())->include);
    }
}
