<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Extension\RedundantDiagnostic;

use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Nop;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Toolkit\PhpStan\Extension\RedundantDiagnostic\TestControlFlowDiagnosticPolicy;

/**
 * @covers \Toolkit\PhpStan\Extension\RedundantDiagnostic\TestControlFlowDiagnosticPolicy
 */
#[CoversClass(TestControlFlowDiagnosticPolicy::class)]
#[Medium]
final class TestControlFlowDiagnosticPolicyTest extends PHPStanTestCase
{
    public function testIsRedundantRecognizesProhibitedControlFlowInNamedTestMethod(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getClassReflection')->willReturn(
            self::createReflectionProvider()->getClass('Tests\Unit\Fixture\TestClassScope\ClassInUnitNamespace')
        );
        $scope->method('getFunctionName')->willReturn('testExample');

        $policy = new TestControlFlowDiagnosticPolicy();

        self::assertTrue($policy->isRedundant(new If_(new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('true'))), $scope, true));
        self::assertFalse($policy->isRedundant(new Nop(), $scope, true));
        self::assertFalse($policy->isRedundant(new If_(new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('true'))), $scope, false));
    }

    public function testIsTestMethodRecognizesAttributeAndRejectsProvider(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getClassReflection')->willReturn(
            self::createReflectionProvider()->getClass('Tests\Unit\Fixture\TestClassScope\ClassWithAttributeTest')
        );
        $scope->method('getFunctionName')->willReturn('checksExample');

        self::assertTrue((new TestControlFlowDiagnosticPolicy())->isTestMethod($scope));

        $providerScope = self::createStub(Scope::class);
        $providerScope->method('getClassReflection')->willReturn(
            self::createReflectionProvider()->getClass('Tests\Unit\Fixture\TestClassScope\ClassWithAttributeTest')
        );
        $providerScope->method('getFunctionName')->willReturn('providerExamples');

        self::assertFalse((new TestControlFlowDiagnosticPolicy())->isTestMethod($providerScope));
    }

    public function testIsTestMethodRejectsNonMethodScope(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getFunctionName')->willReturn(null);

        self::assertFalse((new TestControlFlowDiagnosticPolicy())->isTestMethod($scope));
    }
}
