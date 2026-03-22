<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PhpStanAiRules\Support\TestClassScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

#[CoversClass(TestClassScope::class)]
#[Medium]
final class TestClassScopeTest extends PHPStanTestCase
{
    public function testIsTestClassReturnsTrueForTestNamespace(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getClassReflection')->willReturn(
            self::createReflectionProvider()->getClass('Tests\Fixture\TestClassScope\ClassInTestNamespace')
        );

        $testClassScope = new TestClassScope();

        self::assertTrue($testClassScope->isTestClass($scope));
    }

    public function testIsTestClassReturnsFalseForNonTestNamespace(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getClassReflection')->willReturn(
            self::createReflectionProvider()->getClass('PhpStanAiRules\Support\TestClassScope')
        );

        $testClassScope = new TestClassScope();

        self::assertFalse($testClassScope->isTestClass($scope));
    }

    public function testIsTestClassReturnsFalseWhenNoClassReflection(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getClassReflection')->willReturn(null);

        $testClassScope = new TestClassScope();

        self::assertFalse($testClassScope->isTestClass($scope));
    }

    public function testIsRestrictedTestClassReturnsTrueForUnitNamespace(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getClassReflection')->willReturn(
            self::createReflectionProvider()->getClass('Tests\Unit\Fixture\TestClassScope\ClassInUnitNamespace')
        );

        $testClassScope = new TestClassScope();

        self::assertTrue($testClassScope->isRestrictedTestClass($scope));
    }

    public function testIsRestrictedTestClassReturnsFalseForNonRestrictedTestNamespace(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getClassReflection')->willReturn(
            self::createReflectionProvider()->getClass('Tests\Fixture\TestClassScope\ClassInTestNamespace')
        );

        $testClassScope = new TestClassScope();

        self::assertFalse($testClassScope->isRestrictedTestClass($scope));
    }

    public function testIsRestrictedTestClassReturnsFalseForNonTestNamespace(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getClassReflection')->willReturn(
            self::createReflectionProvider()->getClass('PhpStanAiRules\Support\TestClassScope')
        );

        $testClassScope = new TestClassScope();

        self::assertFalse($testClassScope->isRestrictedTestClass($scope));
    }
}
