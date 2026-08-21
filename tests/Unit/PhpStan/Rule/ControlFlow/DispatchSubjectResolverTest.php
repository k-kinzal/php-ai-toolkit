<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ControlFlow;

use PhpAiToolkit\PhpStan\Rule\ControlFlow\DispatchSubjectResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\StringType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DispatchSubjectResolver::class)]
final class DispatchSubjectResolverTest extends TestCase
{
    public function testResolveReturnsTheSubjectWrittenNextToTheKeyword(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new StringType());
        $condition = new \PhpParser\Node\Expr\Variable('mode');

        self::assertSame($condition, (new DispatchSubjectResolver())->resolve($condition, [], $scope));
    }

    public function testResolveReadsTheSubjectOutOfTheBranchesOfATrueDispatch(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ConstantBooleanType(true));
        $subject = new \PhpParser\Node\Expr\Variable('shape');
        $branches = [
            new \PhpParser\Node\Expr\Instanceof_($subject, new \PhpParser\Node\Name\FullyQualified('App\\Circle')),
            new \PhpParser\Node\Expr\Instanceof_(
                new \PhpParser\Node\Expr\Variable('shape'),
                new \PhpParser\Node\Name\FullyQualified('App\\Square'),
            ),
        ];

        self::assertSame($subject, (new DispatchSubjectResolver())->resolve(
            new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('true')),
            $branches,
            $scope,
        ));
    }

    public function testResolveReturnsNullWhenTheBranchesNarrowDifferentSubjects(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ConstantBooleanType(false));
        $branches = [
            new \PhpParser\Node\Expr\Instanceof_(
                new \PhpParser\Node\Expr\Variable('shape'),
                new \PhpParser\Node\Name\FullyQualified('App\\Circle'),
            ),
            new \PhpParser\Node\Expr\Instanceof_(
                new \PhpParser\Node\Expr\Variable('other'),
                new \PhpParser\Node\Name\FullyQualified('App\\Square'),
            ),
        ];

        self::assertNull((new DispatchSubjectResolver())->resolve(
            new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('false')),
            $branches,
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

    public function testCommonSubjectReturnsNullWithoutBranches(): void
    {
        self::assertNull((new DispatchSubjectResolver())->commonSubject([]));
    }

    public function testCommonSubjectReturnsNullWhenOneBranchNarrowsNothing(): void
    {
        $branches = [
            new \PhpParser\Node\Expr\Instanceof_(
                new \PhpParser\Node\Expr\Variable('shape'),
                new \PhpParser\Node\Name\FullyQualified('App\\Circle'),
            ),
            new \PhpParser\Node\Expr\BinaryOp\Greater(
                new \PhpParser\Node\Expr\Variable('size'),
                new \PhpParser\Node\Scalar\String_('10'),
            ),
        ];

        self::assertNull((new DispatchSubjectResolver())->commonSubject($branches));
    }

    public function testNarrowedExpressionReadsTheSubjectOfAnInstanceOf(): void
    {
        $subject = new \PhpParser\Node\Expr\Variable('shape');

        self::assertSame($subject, (new DispatchSubjectResolver())->narrowedExpression(
            new \PhpParser\Node\Expr\Instanceof_($subject, new \PhpParser\Node\Name\FullyQualified('App\\Circle')),
        ));
    }

    public function testNarrowedExpressionReadsTheSubjectLeftOfAnIdenticalComparison(): void
    {
        $subject = new \PhpParser\Node\Expr\Variable('suit');

        self::assertSame($subject, (new DispatchSubjectResolver())->narrowedExpression(
            new \PhpParser\Node\Expr\BinaryOp\Identical($subject, new \PhpParser\Node\Scalar\String_('hearts')),
        ));
    }

    public function testNarrowedExpressionReadsTheSubjectRightOfANotIdenticalComparison(): void
    {
        $subject = new \PhpParser\Node\Expr\Variable('suit');

        self::assertSame($subject, (new DispatchSubjectResolver())->narrowedExpression(
            new \PhpParser\Node\Expr\BinaryOp\NotIdentical(
                new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('null')),
                $subject,
            ),
        ));
    }

    public function testNarrowedExpressionReadsTheSubjectOfALooseComparison(): void
    {
        $subject = new \PhpParser\Node\Expr\Variable('suit');

        self::assertSame($subject, (new DispatchSubjectResolver())->narrowedExpression(
            new \PhpParser\Node\Expr\BinaryOp\Equal($subject, new \PhpParser\Node\Scalar\String_('hearts')),
        ));
    }

    public function testNarrowedExpressionReadsTheSubjectOfALooseNegatedComparison(): void
    {
        $subject = new \PhpParser\Node\Expr\Variable('suit');

        self::assertSame($subject, (new DispatchSubjectResolver())->narrowedExpression(
            new \PhpParser\Node\Expr\BinaryOp\NotEqual($subject, new \PhpParser\Node\Scalar\String_('hearts')),
        ));
    }

    public function testNarrowedExpressionReturnsNullWhenBothSidesAreConstant(): void
    {
        self::assertNull((new DispatchSubjectResolver())->narrowedExpression(
            new \PhpParser\Node\Expr\BinaryOp\Identical(
                new \PhpParser\Node\Scalar\String_('a'),
                new \PhpParser\Node\Scalar\String_('b'),
            ),
        ));
    }

    public function testNarrowedExpressionReturnsNullWhenNeitherSideIsConstant(): void
    {
        self::assertNull((new DispatchSubjectResolver())->narrowedExpression(
            new \PhpParser\Node\Expr\BinaryOp\Identical(
                new \PhpParser\Node\Expr\Variable('left'),
                new \PhpParser\Node\Expr\Variable('right'),
            ),
        ));
    }

    public function testNarrowedExpressionReturnsNullForAnotherOperator(): void
    {
        self::assertNull((new DispatchSubjectResolver())->narrowedExpression(
            new \PhpParser\Node\Expr\BinaryOp\Greater(
                new \PhpParser\Node\Expr\Variable('size'),
                new \PhpParser\Node\Scalar\String_('10'),
            ),
        ));
    }

    public function testIsConstantExpressionAcceptsAClassConstant(): void
    {
        self::assertTrue((new DispatchSubjectResolver())->isConstantExpression(
            new \PhpParser\Node\Expr\ClassConstFetch(
                new \PhpParser\Node\Name\FullyQualified('App\\Suit'),
                new \PhpParser\Node\Identifier('Hearts'),
            ),
        ));
    }

    public function testIsConstantExpressionAcceptsAGlobalConstant(): void
    {
        self::assertTrue((new DispatchSubjectResolver())->isConstantExpression(
            new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('null')),
        ));
    }

    public function testIsConstantExpressionRejectsAVariable(): void
    {
        self::assertFalse((new DispatchSubjectResolver())->isConstantExpression(new \PhpParser\Node\Expr\Variable('suit')));
    }
}
