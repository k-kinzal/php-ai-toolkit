<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\NamespaceVisibility;

use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\ReferencedClassResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Tests\Fixture\NamespaceVisibility\Package\NamespaceScoped;

#[CoversClass(ReferencedClassResolver::class)]
#[Medium]
final class ReferencedClassResolverTest extends PHPStanTestCase
{
    public function testFromNodeResolvesANamedClass(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('resolveTypeByName')->willReturn(new ObjectType($class->getName()));

        self::assertCount(1, (new ReferencedClassResolver())->fromNode(new \PhpParser\Node\Name($class->getName()), $scope));
    }

    public function testFromNodeResolvesTheTypeBehindAnExpression(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ObjectType($class->getName()));

        self::assertCount(1, (new ReferencedClassResolver())->fromNode(new \PhpParser\Node\Expr\Variable('scoped'), $scope));
    }

    public function testFromNodeIgnoresParentKeywordSpelledInUppercase(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('resolveTypeByName')->willReturn(new ObjectType($class->getName()));

        self::assertSame([], (new ReferencedClassResolver())->fromNode(new \PhpParser\Node\Name('PARENT'), $scope));
    }

    public function testFromNodeIgnoresSelfKeyword(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('resolveTypeByName')->willReturn(new ObjectType($class->getName()));

        self::assertSame([], (new ReferencedClassResolver())->fromNode(new \PhpParser\Node\Name('self'), $scope));
    }

    public function testFromNodeIgnoresStaticKeyword(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('resolveTypeByName')->willReturn(new ObjectType($class->getName()));

        self::assertSame([], (new ReferencedClassResolver())->fromNode(new \PhpParser\Node\Name('STATIC'), $scope));
    }

    public function testFromNodeIgnoresParentKeyword(): void
    {
        $class = self::createReflectionProvider()->getClass(NamespaceScoped::class);
        $scope = self::createStub(Scope::class);
        $scope->method('resolveTypeByName')->willReturn(new ObjectType($class->getName()));

        self::assertSame([], (new ReferencedClassResolver())->fromNode(new \PhpParser\Node\Name('parent'), $scope));
    }

    public function testFromNodeIgnoresAnonymousClassDeclaration(): void
    {
        self::assertSame([], (new ReferencedClassResolver())->fromNode(new \PhpParser\Node\Stmt\Class_(null), self::createStub(Scope::class)));
    }

    public function testNamesInReturnsPlainClassName(): void
    {
        $names = (new ReferencedClassResolver())->namesIn(new \PhpParser\Node\Name('App\\Domain\\Order'));

        self::assertSame('App\\Domain\\Order', $names[0]->toString());
    }

    public function testNamesInUnwrapsNullableType(): void
    {
        $names = (new ReferencedClassResolver())->namesIn(new \PhpParser\Node\NullableType(new \PhpParser\Node\Name('App\\Domain\\Order')));

        self::assertSame('App\\Domain\\Order', $names[0]->toString());
    }

    public function testNamesInUnwrapsUnionType(): void
    {
        $union = new \PhpParser\Node\UnionType([new \PhpParser\Node\Name('App\\Domain\\Order'), new \PhpParser\Node\Name('App\\Domain\\Cart')]);

        self::assertCount(2, (new ReferencedClassResolver())->namesIn($union));
    }

    public function testNamesInUnwrapsIntersectionInsideUnion(): void
    {
        $intersection = new \PhpParser\Node\IntersectionType([new \PhpParser\Node\Name('App\\Domain\\Order')]);
        $union = new \PhpParser\Node\UnionType([$intersection, new \PhpParser\Node\Identifier('null')]);

        self::assertCount(1, (new ReferencedClassResolver())->namesIn($union));
    }

    public function testNamesInIgnoresBuiltInType(): void
    {
        self::assertSame([], (new ReferencedClassResolver())->namesIn(new \PhpParser\Node\Identifier('int')));
    }

    public function testNamesInIgnoresAbsentType(): void
    {
        self::assertSame([], (new ReferencedClassResolver())->namesIn(null));
    }
}
