<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ControlFlow;

use PhpAiToolkit\PhpStan\Rule\ControlFlow\DispatchSubjectResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\StringType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\ControlFlow\DispatchSubjectResolver
 */
#[CoversClass(DispatchSubjectResolver::class)]
final class DispatchSubjectResolverTest extends TestCase
{
    public function testResolveReturnsTheSubjectWrittenNextToTheKeyword(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new StringType());
        $condition = new \PhpParser\Node\Expr\Variable('mode');

        self::assertSame($condition, (new DispatchSubjectResolver())->resolve($condition, $scope));
    }

    public function testResolveNamesNoSubjectForAConstantTrueDispatch(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ConstantBooleanType(true));

        self::assertNull((new DispatchSubjectResolver())->resolve(
            new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('true')),
            $scope,
        ));
    }

    public function testResolveNamesNoSubjectForAConstantFalseDispatch(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ConstantBooleanType(false));

        self::assertNull((new DispatchSubjectResolver())->resolve(
            new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('false')),
            $scope,
        ));
    }

    public function testNamedObjectReadsTheObjectOfAClassConstantSubject(): void
    {
        $object = new \PhpParser\Node\Expr\Variable('shape');

        self::assertSame($object, (new DispatchSubjectResolver())->namedObject(
            new \PhpParser\Node\Expr\ClassConstFetch($object, new \PhpParser\Node\Identifier('class')),
        ));
    }

    public function testNamedObjectReadsTheArgumentOfGetClass(): void
    {
        $object = new \PhpParser\Node\Expr\Variable('shape');

        self::assertSame($object, (new DispatchSubjectResolver())->namedObject(
            new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('get_class'), [new \PhpParser\Node\Arg($object)]),
        ));
    }

    public function testNamedObjectReturnsNullForAWrittenClassName(): void
    {
        self::assertNull((new DispatchSubjectResolver())->namedObject(
            new \PhpParser\Node\Expr\ClassConstFetch(
                new \PhpParser\Node\Name\FullyQualified('App\\Circle'),
                new \PhpParser\Node\Identifier('class'),
            ),
        ));
    }

    public function testNamedObjectReturnsNullForAnotherClassConstant(): void
    {
        self::assertNull((new DispatchSubjectResolver())->namedObject(
            new \PhpParser\Node\Expr\ClassConstFetch(
                new \PhpParser\Node\Expr\Variable('shape'),
                new \PhpParser\Node\Identifier('MODE'),
            ),
        ));
    }

    public function testNamedObjectReturnsNullForAnotherFunction(): void
    {
        self::assertNull((new DispatchSubjectResolver())->namedObject(
            new \PhpParser\Node\Expr\FuncCall(
                new \PhpParser\Node\Name('strtolower'),
                [new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('shape'))],
            ),
        ));
    }

    public function testNamedObjectReturnsNullForGetClassWithoutOneArgument(): void
    {
        self::assertNull((new DispatchSubjectResolver())->namedObject(
            new \PhpParser\Node\Expr\FuncCall(new \PhpParser\Node\Name('get_class'), []),
        ));
    }

    public function testNamedObjectReturnsNullForAnExpressionThatNamesNoClass(): void
    {
        self::assertNull((new DispatchSubjectResolver())->namedObject(new \PhpParser\Node\Expr\Variable('shape')));
    }
}
