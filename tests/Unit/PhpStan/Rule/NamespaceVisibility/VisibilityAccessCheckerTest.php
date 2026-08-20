<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\NamespaceVisibility;

use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\NamespaceLineage;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityAccessChecker;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityScope;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityScopeResolver;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityTagParser;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Fixture\NamespaceVisibility\Package\MemberScoped;
use Tests\Fixture\NamespaceVisibility\Package\NamespaceScoped;
use Tests\Fixture\NamespaceVisibility\Package\PublicScoped;
use Tests\Fixture\NamespaceVisibility\Package\ScopedContract;
use Tests\Fixture\NamespaceVisibility\Package\ScopedSuit;
use Tests\Fixture\NamespaceVisibility\Package\ScopedTrait;

#[CoversClass(VisibilityAccessChecker::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(VisibilityErrorBuilder::class)]
#[UsesClass(VisibilityScope::class)]
#[UsesClass(VisibilityScopeResolver::class)]
#[UsesClass(VisibilityTagParser::class)]
#[Medium]
final class VisibilityAccessCheckerTest extends PHPStanTestCase
{
    public function testCheckClassReportsScopedClassFromOutside(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $error = (new VisibilityAccessChecker())->checkClass($class, 'Other\\Place', 5);

        self::assertNotNull($error);
    }

    public function testCheckClassNamesTheClassItReports(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $error = (new VisibilityAccessChecker())->checkClass($class, 'Other\\Place', 5);

        self::assertStringStartsWith('Class Tests\\Fixture\\NamespaceVisibility\\Package\\NamespaceScoped is not visible', $error?->getMessage() ?? '');
    }

    public function testCheckClassAcceptsCallerInDeclaringNamespace(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);

        self::assertNull((new VisibilityAccessChecker())->checkClass($class, 'Tests\\Fixture\\NamespaceVisibility\\Package', 5));
    }

    public function testCheckClassAcceptsPublicDeclaration(): void
    {
        $class = self::createReflectionProvider()->getClass(PublicScoped::class);

        self::assertNull((new VisibilityAccessChecker())->checkClass($class, 'Other\\Place', 5));
    }

    public function testCheckMethodReportsScopedMethodOfUnscopedClass(): void
    {
        $class = self::createReflectionProvider()->getClass(MemberScoped::class);
        $error = (new VisibilityAccessChecker())->checkMethod($class, 'internalRun', 'Other\\Place', 5);

        self::assertStringStartsWith('Call to Tests\\Fixture\\NamespaceVisibility\\Package\\MemberScoped::internalRun() is not visible', $error?->getMessage() ?? '');
    }

    public function testCheckMethodAcceptsUnscopedMethodOfUnscopedClass(): void
    {
        $class = self::createReflectionProvider()->getClass(MemberScoped::class);

        self::assertNull((new VisibilityAccessChecker())->checkMethod($class, 'publicRun', 'Other\\Place', 5));
    }

    public function testCheckMethodFallsBackToClassScope(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $error = (new VisibilityAccessChecker())->checkMethod($class, 'run', 'Other\\Place', 5);

        self::assertStringStartsWith('Class Tests\\Fixture\\NamespaceVisibility\\Package\\NamespaceScoped is not visible', $error?->getMessage() ?? '');
    }

    public function testCheckMethodFallsBackToClassScopeForAbsentMethod(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $error = (new VisibilityAccessChecker())->checkMethod($class, '__construct', 'Other\\Place', 5);

        self::assertStringStartsWith('Class Tests\\Fixture\\NamespaceVisibility\\Package\\NamespaceScoped is not visible', $error?->getMessage() ?? '');
    }

    public function testCheckPropertyReportsScopedProperty(): void
    {
        $class = self::createReflectionProvider()->getClass(MemberScoped::class);
        $error = (new VisibilityAccessChecker())->checkProperty($class, 'state', 'Other\\Place', 5);

        self::assertStringStartsWith('Access to property Tests\\Fixture\\NamespaceVisibility\\Package\\MemberScoped::$state is not visible', $error?->getMessage() ?? '');
    }

    public function testCheckPropertyFallsBackToClassScopeForAbsentProperty(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $error = (new VisibilityAccessChecker())->checkProperty($class, 'missing', 'Other\\Place', 5);

        self::assertStringStartsWith('Class Tests\\Fixture\\NamespaceVisibility\\Package\\NamespaceScoped is not visible', $error?->getMessage() ?? '');
    }

    public function testCheckConstantReportsScopedConstant(): void
    {
        $class = self::createReflectionProvider()->getClass(MemberScoped::class);
        $error = (new VisibilityAccessChecker())->checkConstant($class, 'SECRET', 'Other\\Place', 5);

        self::assertStringStartsWith('Access to constant Tests\\Fixture\\NamespaceVisibility\\Package\\MemberScoped::SECRET is not visible', $error?->getMessage() ?? '');
    }

    public function testCheckConstantFallsBackToClassScopeForAbsentConstant(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $error = (new VisibilityAccessChecker())->checkConstant($class, 'MISSING', 'Other\\Place', 5);

        self::assertStringStartsWith('Class Tests\\Fixture\\NamespaceVisibility\\Package\\NamespaceScoped is not visible', $error?->getMessage() ?? '');
    }

    public function testCheckDocCommentReportsScopeOfAnyDeclaration(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $error = (new VisibilityAccessChecker())->checkDocComment('/** @visibility namespace */', $class, 'Anything', 'Other\\Place', 5);

        self::assertStringStartsWith('Anything is not visible', $error?->getMessage() ?? '');
    }

    public function testCheckDocCommentAcceptsAnUntaggedDeclaration(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);

        self::assertNull((new VisibilityAccessChecker())->checkDocComment(null, $class, 'Anything', 'Other\\Place', 5));
    }

    public function testKindOfNamesAnInterface(): void
    {
        $class = self::createReflectionProvider()->getClass(ScopedContract::class);

        self::assertSame('Interface', (new VisibilityAccessChecker())->kindOf($class));
    }

    public function testKindOfNamesATrait(): void
    {
        $class = self::createReflectionProvider()->getClass(ScopedTrait::class);

        self::assertSame('Trait', (new VisibilityAccessChecker())->kindOf($class));
    }

    public function testKindOfNamesAnEnum(): void
    {
        $class = self::createReflectionProvider()->getClass(ScopedSuit::class);

        self::assertSame('Enum', (new VisibilityAccessChecker())->kindOf($class));
    }

    public function testKindOfNamesAClass(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);

        self::assertSame('Class', (new VisibilityAccessChecker())->kindOf($class));
    }
}
