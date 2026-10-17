<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ControlFlow;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Case_;
use PhpParser\Node\Stmt\Switch_;
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
        self::assertSame(Node::class, (new ClassNameDispatchCollector())->getNodeType());
    }

    public function testProcessNodeIgnoresNodesThatBranchOnNothing(): void
    {
        self::assertNull((new ClassNameDispatchCollector())->processNode(
            new Variable('payment'),
            self::createStub(Scope::class),
        ));
    }

    public function testProcessNodeReadsAMatchOnAClassName(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturnCallback(
            static fn (Expr $expression): Type => $expression instanceof Variable
                ? new ObjectType('App\\Payment')
                : ($expression instanceof ClassConstFetch && $expression->class instanceof Name
                    ? new ConstantStringType('App\\Visa')
                    : new StringType()),
        );
        $subject = new ClassConstFetch(
            new Variable('payment'),
            new Identifier('class'),
        );
        $node = new Match_($subject, [
            new MatchArm(
                [new ClassConstFetch(new FullyQualified('App\\Visa'), new Identifier('class'))],
                new String_('visa'),
            ),
            new MatchArm(null, new String_('other')),
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
        $subject = new ClassConstFetch(
            new Variable('payment'),
            new Identifier('class'),
        );
        $node = new Match_($subject, [
            new MatchArm(
                [new ClassConstFetch(new FullyQualified('App\\Visa'), new Identifier('class'))],
                new String_('visa'),
            ),
        ], ['startLine' => 6]);

        self::assertNull((new ClassNameDispatchCollector())->processNode($node, self::createStub(Scope::class)));
    }

    public function testProcessNodeReadsASwitchOnAClassName(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturnCallback(
            static fn (Expr $expression): Type => $expression instanceof Variable
                ? new ObjectType('App\\Payment')
                : ($expression instanceof ClassConstFetch && $expression->class instanceof Name
                    ? new ConstantStringType('App\\Visa')
                    : new StringType()),
        );
        $subject = new ClassConstFetch(
            new Variable('payment'),
            new Identifier('class'),
        );
        $node = new Switch_($subject, [
            new Case_(
                new ClassConstFetch(new FullyQualified('App\\Visa'), new Identifier('class')),
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
            new Variable('mode'),
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
        $subject = new ClassConstFetch(
            new Variable('payment'),
            new Identifier('class'),
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
        $subject = new ClassConstFetch(
            new Variable('payment'),
            new Identifier('class'),
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
            (new ClassNameDispatchCollector())->namedClasses([new Variable('anything')], $scope),
        );
    }

    public function testNamedClassesReturnsNullWhenABranchNamesSomethingElse(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new StringType());

        self::assertNull((new ClassNameDispatchCollector())->namedClasses([new Variable('anything')], $scope));
    }
}
