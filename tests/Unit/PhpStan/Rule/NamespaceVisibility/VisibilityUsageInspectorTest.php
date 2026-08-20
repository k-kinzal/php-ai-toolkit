<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\NamespaceVisibility;

use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\NamespaceLineage;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\ReferencedClassResolver;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityAccessChecker;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityScope;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityScopeResolver;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityTagParser;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityUsageInspector;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Fixture\NamespaceVisibility\Package\MemberScoped;
use Tests\Fixture\NamespaceVisibility\Package\NamespaceScoped;

#[CoversClass(VisibilityUsageInspector::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(ReferencedClassResolver::class)]
#[UsesClass(VisibilityAccessChecker::class)]
#[UsesClass(VisibilityErrorBuilder::class)]
#[UsesClass(VisibilityScope::class)]
#[UsesClass(VisibilityScopeResolver::class)]
#[UsesClass(VisibilityTagParser::class)]
#[Medium]
final class VisibilityUsageInspectorTest extends PHPStanTestCase
{
    public function testNameOfReadsIdentifier(): void
    {
        self::assertSame('run', (new VisibilityUsageInspector())->nameOf(new \PhpParser\Node\Identifier('run')));
    }

    public function testNameOfReadsPropertyIdentifier(): void
    {
        self::assertSame('state', (new VisibilityUsageInspector())->nameOf(new \PhpParser\Node\VarLikeIdentifier('state')));
    }

    public function testNameOfIgnoresComputedName(): void
    {
        self::assertNull((new VisibilityUsageInspector())->nameOf(new \PhpParser\Node\Expr\Variable('method')));
    }

    public function testMethodErrorsReportsScopedMethod(): void
    {
        $classes = [self::createReflectionProvider()->getClass(MemberScoped::class)];
        $errors = (new VisibilityUsageInspector())->methodErrors($classes, 'internalRun', 'Other\\Place', 9);

        self::assertCount(1, $errors);
    }

    public function testMethodErrorsFallsBackToClassScopeForComputedName(): void
    {
        $classes = [self::createReflectionProvider()->getClass(NamespaceScoped::class)];
        $errors = (new VisibilityUsageInspector())->methodErrors($classes, null, 'Other\\Place', 9);

        self::assertStringStartsWith('Class ', $errors[0]->getMessage());
    }

    public function testPropertyErrorsReportsScopedProperty(): void
    {
        $classes = [self::createReflectionProvider()->getClass(MemberScoped::class)];
        $errors = (new VisibilityUsageInspector())->propertyErrors($classes, 'state', 'Other\\Place', 9);

        self::assertCount(1, $errors);
    }

    public function testPropertyErrorsFallsBackToClassScopeForComputedName(): void
    {
        $classes = [self::createReflectionProvider()->getClass(NamespaceScoped::class)];

        self::assertCount(1, (new VisibilityUsageInspector())->propertyErrors($classes, null, 'Other\\Place', 9));
    }

    public function testConstantErrorsReportsScopedConstant(): void
    {
        $classes = [self::createReflectionProvider()->getClass(MemberScoped::class)];
        $errors = (new VisibilityUsageInspector())->constantErrors($classes, 'SECRET', 'Other\\Place', 9);

        self::assertCount(1, $errors);
    }

    public function testConstantErrorsTreatsClassKeywordAsClassReference(): void
    {
        $classes = [self::createReflectionProvider()->getClass(MemberScoped::class)];

        self::assertSame([], (new VisibilityUsageInspector())->constantErrors($classes, 'class', 'Other\\Place', 9));
    }

    public function testClassErrorsReportsScopedClass(): void
    {
        $classes = [self::createReflectionProvider()->getClass(NamespaceScoped::class)];

        self::assertCount(1, (new VisibilityUsageInspector())->classErrors($classes, 'Other\\Place', 9));
    }

    public function testClassErrorsReturnsNothingWithoutClasses(): void
    {
        self::assertSame([], (new VisibilityUsageInspector())->classErrors([], 'Other\\Place', 9));
    }

    public function testErrorsReportsInstantiationOfAScopedClass(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('resolveTypeByName')->willReturn(new ObjectType($class->getName()));
        $node = new \PhpParser\Node\Expr\New_(new \PhpParser\Node\Name($class->getName()));

        self::assertCount(1, (new VisibilityUsageInspector())->errors($node, $scope, 'Other\\Place'));
    }

    public function testErrorsReportsStaticCallOnAScopedClass(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('resolveTypeByName')->willReturn(new ObjectType($class->getName()));
        $node = new \PhpParser\Node\Expr\StaticCall(new \PhpParser\Node\Name($class->getName()), 'make');

        self::assertCount(1, (new VisibilityUsageInspector())->errors($node, $scope, 'Other\\Place'));
    }

    public function testErrorsReportsMethodCallOnAScopedMember(): void
    {
        $class = self::createReflectionProvider()->getClass(MemberScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ObjectType($class->getName()));
        $node = new \PhpParser\Node\Expr\MethodCall(new \PhpParser\Node\Expr\Variable('scoped'), 'internalRun');

        self::assertCount(1, (new VisibilityUsageInspector())->errors($node, $scope, 'Other\\Place'));
    }

    public function testErrorsReportsNullsafeMethodCallOnAScopedMember(): void
    {
        $class = self::createReflectionProvider()->getClass(MemberScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ObjectType($class->getName()));
        $node = new \PhpParser\Node\Expr\NullsafeMethodCall(new \PhpParser\Node\Expr\Variable('scoped'), 'internalRun');

        self::assertCount(1, (new VisibilityUsageInspector())->errors($node, $scope, 'Other\\Place'));
    }

    public function testErrorsReportsPropertyFetchOnAScopedMember(): void
    {
        $class = self::createReflectionProvider()->getClass(MemberScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ObjectType($class->getName()));
        $node = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('scoped'), 'state');

        self::assertCount(1, (new VisibilityUsageInspector())->errors($node, $scope, 'Other\\Place'));
    }

    public function testErrorsReportsNullsafePropertyFetchOnAScopedMember(): void
    {
        $class = self::createReflectionProvider()->getClass(MemberScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ObjectType($class->getName()));
        $node = new \PhpParser\Node\Expr\NullsafePropertyFetch(new \PhpParser\Node\Expr\Variable('scoped'), 'state');

        self::assertCount(1, (new VisibilityUsageInspector())->errors($node, $scope, 'Other\\Place'));
    }

    public function testErrorsReportsStaticPropertyFetchOnAScopedMember(): void
    {
        $class = self::createReflectionProvider()->getClass(MemberScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('resolveTypeByName')->willReturn(new ObjectType($class->getName()));
        $node = new \PhpParser\Node\Expr\StaticPropertyFetch(new \PhpParser\Node\Name($class->getName()), 'sharedState');

        self::assertCount(1, (new VisibilityUsageInspector())->errors($node, $scope, 'Other\\Place'));
    }

    public function testErrorsReportsClassConstantFetchOnAScopedMember(): void
    {
        $class = self::createReflectionProvider()->getClass(MemberScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('resolveTypeByName')->willReturn(new ObjectType($class->getName()));
        $node = new \PhpParser\Node\Expr\ClassConstFetch(new \PhpParser\Node\Name($class->getName()), 'SECRET');

        self::assertCount(1, (new VisibilityUsageInspector())->errors($node, $scope, 'Other\\Place'));
    }

    public function testErrorsReportsInstanceofCheckAgainstAScopedClass(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('resolveTypeByName')->willReturn(new ObjectType($class->getName()));
        $node = new \PhpParser\Node\Expr\Instanceof_(new \PhpParser\Node\Expr\Variable('candidate'), new \PhpParser\Node\Name($class->getName()));

        self::assertCount(1, (new VisibilityUsageInspector())->errors($node, $scope, 'Other\\Place'));
    }

    public function testErrorsIgnoresInstantiationOfSelf(): void
    {
        $node = new \PhpParser\Node\Expr\New_(new \PhpParser\Node\Name('self'));

        self::assertSame([], (new VisibilityUsageInspector())->errors($node, self::createStub(Scope::class), 'Other\\Place'));
    }

    public function testErrorsIgnoresUnrelatedExpression(): void
    {
        self::assertSame([], (new VisibilityUsageInspector())->errors(new \PhpParser\Node\Expr\Variable('order'), self::createStub(Scope::class), 'Other\\Place'));
    }
}
