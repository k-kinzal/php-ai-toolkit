<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ControlFlow;

use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ControlFlow\ClassNameDispatchCollector;
use Toolkit\PhpStan\Rule\ControlFlow\DispatchSubjectResolver;

/**
 * @covers \Toolkit\PhpStan\Rule\ControlFlow\ClassNameDispatchCollector
 * @uses \Toolkit\PhpStan\Rule\ControlFlow\DispatchSubjectResolver
 */
#[CoversClass(ClassNameDispatchCollector::class)]
#[UsesClass(DispatchSubjectResolver::class)]
final class ClassNameDispatchCollectorTest extends TestCase
{
    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node::class, (new ClassNameDispatchCollector())->getNodeType());
    }

    public function testProcessNodeIgnoresNodesThatBranchOnNothing(): void
    {
        self::assertNull((new ClassNameDispatchCollector())->processNode(
            new \PhpParser\Node\Expr\Variable('payment'),
            self::createStub(Scope::class),
        ));
    }

    public function testProcessNodeReadsAMatchOnAClassName(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturnCallback(
            static fn (\PhpParser\Node\Expr $expression): Type => $expression instanceof \PhpParser\Node\Expr\Variable
                ? new ObjectType('App\\Payment')
                : ($expression instanceof \PhpParser\Node\Expr\ClassConstFetch && $expression->class instanceof \PhpParser\Node\Name
                    ? new ConstantStringType('App\\Visa')
                    : new StringType()),
        );
        $subject = new \PhpParser\Node\Expr\ClassConstFetch(
            new \PhpParser\Node\Expr\Variable('payment'),
            new \PhpParser\Node\Identifier('class'),
        );
        $node = new \PhpParser\Node\Expr\Match_($subject, [
            new \PhpParser\Node\MatchArm(
                [new \PhpParser\Node\Expr\ClassConstFetch(new \PhpParser\Node\Name\FullyQualified('App\\Visa'), new \PhpParser\Node\Identifier('class'))],
                new \PhpParser\Node\Scalar\String_('visa'),
            ),
            new \PhpParser\Node\MatchArm(null, new \PhpParser\Node\Scalar\String_('other')),
        ], ['startLine' => 6]);

        self::assertSame(
            [
                'roots' => ['App\\Payment'],
                'named' => ['App\\Visa'],
                'catchAll' => true,
                'line' => 6,
                'construct' => ClassNameDispatchCollector::MATCH_CONSTRUCT,
            ],
            (new ClassNameDispatchCollector())->processNode($node, $scope),
        );
    }

    public function testProcessNodeSkipsAMatchWithoutADefaultArm(): void
    {
        $subject = new \PhpParser\Node\Expr\ClassConstFetch(
            new \PhpParser\Node\Expr\Variable('payment'),
            new \PhpParser\Node\Identifier('class'),
        );
        $node = new \PhpParser\Node\Expr\Match_($subject, [
            new \PhpParser\Node\MatchArm(
                [new \PhpParser\Node\Expr\ClassConstFetch(new \PhpParser\Node\Name\FullyQualified('App\\Visa'), new \PhpParser\Node\Identifier('class'))],
                new \PhpParser\Node\Scalar\String_('visa'),
            ),
        ], ['startLine' => 6]);

        self::assertNull((new ClassNameDispatchCollector())->processNode($node, self::createStub(Scope::class)));
    }

    public function testProcessNodeReadsASwitchOnAClassName(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturnCallback(
            static fn (\PhpParser\Node\Expr $expression): Type => $expression instanceof \PhpParser\Node\Expr\Variable
                ? new ObjectType('App\\Payment')
                : ($expression instanceof \PhpParser\Node\Expr\ClassConstFetch && $expression->class instanceof \PhpParser\Node\Name
                    ? new ConstantStringType('App\\Visa')
                    : new StringType()),
        );
        $subject = new \PhpParser\Node\Expr\ClassConstFetch(
            new \PhpParser\Node\Expr\Variable('payment'),
            new \PhpParser\Node\Identifier('class'),
        );
        $node = new \PhpParser\Node\Stmt\Switch_($subject, [
            new \PhpParser\Node\Stmt\Case_(
                new \PhpParser\Node\Expr\ClassConstFetch(new \PhpParser\Node\Name\FullyQualified('App\\Visa'), new \PhpParser\Node\Identifier('class')),
            ),
        ], ['startLine' => 9]);

        self::assertSame(
            [
                'roots' => ['App\\Payment'],
                'named' => ['App\\Visa'],
                'catchAll' => false,
                'line' => 9,
                'construct' => ClassNameDispatchCollector::SWITCH_CONSTRUCT,
            ],
            (new ClassNameDispatchCollector())->processNode($node, $scope),
        );
    }

    public function testCollectSkipsASubjectThatIsNotAClassName(): void
    {
        self::assertNull((new ClassNameDispatchCollector())->collect(
            new \PhpParser\Node\Expr\Variable('mode'),
            [],
            true,
            ClassNameDispatchCollector::MATCH_CONSTRUCT,
            3,
            self::createStub(Scope::class),
        ));
    }

    public function testCollectSkipsASubjectWhoseValuesAreAlreadyKnown(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ConstantStringType('App\\Visa'));
        $subject = new \PhpParser\Node\Expr\ClassConstFetch(
            new \PhpParser\Node\Expr\Variable('payment'),
            new \PhpParser\Node\Identifier('class'),
        );

        self::assertNull((new ClassNameDispatchCollector())->collect(
            $subject,
            [],
            true,
            ClassNameDispatchCollector::MATCH_CONSTRUCT,
            3,
            $scope,
        ));
    }

    public function testCollectSkipsASubjectWithoutAClass(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new StringType());
        $subject = new \PhpParser\Node\Expr\ClassConstFetch(
            new \PhpParser\Node\Expr\Variable('payment'),
            new \PhpParser\Node\Identifier('class'),
        );

        self::assertNull((new ClassNameDispatchCollector())->collect(
            $subject,
            [],
            true,
            ClassNameDispatchCollector::MATCH_CONSTRUCT,
            3,
            $scope,
        ));
    }

    public function testNamedClassesReadsTheClassOfEachBranch(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ConstantStringType('App\\Visa'));

        self::assertSame(
            ['App\\Visa'],
            (new ClassNameDispatchCollector())->namedClasses([new \PhpParser\Node\Expr\Variable('anything')], $scope),
        );
    }

    public function testNamedClassesReturnsNullWhenABranchNamesSomethingElse(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new StringType());

        self::assertNull((new ClassNameDispatchCollector())->namedClasses([new \PhpParser\Node\Expr\Variable('anything')], $scope));
    }
}
