<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ControlFlow;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\StringType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ControlFlow\DispatchSubjectResolver;

/**
 * @covers \Toolkit\PhpStan\Rule\ControlFlow\DispatchSubjectResolver
 */
#[CoversClass(DispatchSubjectResolver::class)]
final class DispatchSubjectResolverTest extends TestCase
{
    public function testResolveReturnsTheSubjectWrittenNextToTheKeyword(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new StringType());
        $condition = new Variable('mode');

        self::assertSame($condition, (new DispatchSubjectResolver())->resolve($condition, $scope));
    }

    public function testResolveNamesNoSubjectForAConstantTrueDispatch(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ConstantBooleanType(true));

        self::assertNull((new DispatchSubjectResolver())->resolve(
            new ConstFetch(new Name('true')),
            $scope,
        ));
    }

    public function testResolveNamesNoSubjectForAConstantFalseDispatch(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ConstantBooleanType(false));

        self::assertNull((new DispatchSubjectResolver())->resolve(
            new ConstFetch(new Name('false')),
            $scope,
        ));
    }

    public function testNamedObjectReadsTheObjectOfAClassConstantSubject(): void
    {
        $object = new Variable('shape');

        self::assertSame($object, (new DispatchSubjectResolver())->namedObject(
            new ClassConstFetch($object, new Identifier('class')),
        ));
    }

    public function testNamedObjectReadsTheArgumentOfGetClass(): void
    {
        $object = new Variable('shape');

        self::assertSame($object, (new DispatchSubjectResolver())->namedObject(
            new FuncCall(new Name('get_class'), [new Arg($object)]),
        ));
    }

    public function testNamedObjectReturnsNullForAWrittenClassName(): void
    {
        self::assertNull((new DispatchSubjectResolver())->namedObject(
            new ClassConstFetch(
                new FullyQualified('App\\Circle'),
                new Identifier('class'),
            ),
        ));
    }

    public function testNamedObjectReturnsNullForAnotherClassConstant(): void
    {
        self::assertNull((new DispatchSubjectResolver())->namedObject(
            new ClassConstFetch(
                new Variable('shape'),
                new Identifier('MODE'),
            ),
        ));
    }

    public function testNamedObjectReturnsNullForAnotherFunction(): void
    {
        self::assertNull((new DispatchSubjectResolver())->namedObject(
            new FuncCall(
                new Name('strtolower'),
                [new Arg(new Variable('shape'))],
            ),
        ));
    }

    public function testNamedObjectReturnsNullForGetClassWithoutOneArgument(): void
    {
        self::assertNull((new DispatchSubjectResolver())->namedObject(
            new FuncCall(new Name('get_class'), []),
        ));
    }

    public function testNamedObjectReturnsNullForAnExpressionThatNamesNoClass(): void
    {
        self::assertNull((new DispatchSubjectResolver())->namedObject(new Variable('shape')));
    }
}
