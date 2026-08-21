<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestAssertion;

use PhpAiToolkit\PhpStan\Rule\Shared\CallArgumentResolver;
use PhpAiToolkit\PhpStan\Rule\Shared\CallMethodNameResolver;
use PhpAiToolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver;
use PhpAiToolkit\PhpStan\Rule\Shared\PhpUnitCallTargetMatcher;
use PhpAiToolkit\PhpStan\Rule\TestAssertion\BrokenCodeExceptionClassifier;
use PhpAiToolkit\PhpStan\Rule\TestAssertion\ExpectExceptionCallInspector;
use PhpAiToolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationErrorBuilder;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ExpectExceptionCallInspector::class)]
#[UsesClass(BrokenCodeExceptionClassifier::class)]
#[UsesClass(CallArgumentResolver::class)]
#[UsesClass(CallMethodNameResolver::class)]
#[UsesClass(ClassStringExpressionResolver::class)]
#[UsesClass(NoBrokenCodeExpectationErrorBuilder::class)]
#[UsesClass(PhpUnitCallTargetMatcher::class)]
#[Medium]
final class ExpectExceptionCallInspectorTest extends PHPStanTestCase
{
    public function testErrorsReportsInstanceExpectException(): void
    {
        self::createReflectionProvider();
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('LogicException');
        $call = new \PhpParser\Node\Expr\MethodCall(
            new \PhpParser\Node\Expr\Variable('this'),
            'expectException',
            [new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\ClassConstFetch(
                new \PhpParser\Node\Name('LogicException'),
                new \PhpParser\Node\Identifier('class'),
            ))],
        );

        self::assertCount(1, (new ExpectExceptionCallInspector())->errors($call, $scope));
    }

    public function testErrorsReportsStaticExpectException(): void
    {
        self::createReflectionProvider();
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('InvalidArgumentException');
        $call = new \PhpParser\Node\Expr\StaticCall(
            new \PhpParser\Node\Name('self'),
            'expectException',
            [new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\ClassConstFetch(
                new \PhpParser\Node\Name('InvalidArgumentException'),
                new \PhpParser\Node\Identifier('class'),
            ))],
        );

        self::assertCount(1, (new ExpectExceptionCallInspector())->errors($call, $scope));
    }

    public function testErrorsReportsExpectExceptionObject(): void
    {
        self::createReflectionProvider();
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ObjectType('TypeError'));
        $call = new \PhpParser\Node\Expr\MethodCall(
            new \PhpParser\Node\Expr\Variable('this'),
            'expectExceptionObject',
            [new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\Variable('error'))],
        );

        self::assertCount(1, (new ExpectExceptionCallInspector())->errors($call, $scope));
    }

    public function testErrorsIgnoresCallOnAnotherObject(): void
    {
        $call = new \PhpParser\Node\Expr\MethodCall(
            new \PhpParser\Node\Expr\Variable('runner'),
            'expectException',
            [new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\ClassConstFetch(
                new \PhpParser\Node\Name('LogicException'),
                new \PhpParser\Node\Identifier('class'),
            ))],
        );

        self::assertSame([], (new ExpectExceptionCallInspector())->errors($call, self::createStub(Scope::class)));
    }

    public function testErrorsIgnoresStaticCallOnAnotherClass(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\\ReportRunner');
        $call = new \PhpParser\Node\Expr\StaticCall(
            new \PhpParser\Node\Name('ReportRunner'),
            'expectException',
            [new \PhpParser\Node\Arg(new \PhpParser\Node\Expr\ClassConstFetch(
                new \PhpParser\Node\Name('LogicException'),
                new \PhpParser\Node\Identifier('class'),
            ))],
        );

        self::assertSame([], (new ExpectExceptionCallInspector())->errors($call, $scope));
    }

    public function testErrorsIgnoresOtherExpressions(): void
    {
        self::assertSame([], (new ExpectExceptionCallInspector())->errors(
            new \PhpParser\Node\Expr\Variable('report'),
            self::createStub(Scope::class),
        ));
    }

    public function testErrorsForCallIgnoresOtherMethod(): void
    {
        self::assertSame([], (new ExpectExceptionCallInspector())->errorsForCall(
            'expectExceptionMessage',
            new \PhpParser\Node\Scalar\String_('Report source is unreadable.'),
            1,
            self::createStub(Scope::class),
        ));
    }

    public function testErrorsForCallIgnoresMissingArgument(): void
    {
        self::assertSame([], (new ExpectExceptionCallInspector())->errorsForCall(
            'expectException',
            null,
            1,
            self::createStub(Scope::class),
        ));
    }

    public function testErrorsForCallAcceptsRuntimeExceptionFamily(): void
    {
        self::createReflectionProvider();
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('RuntimeException');

        self::assertSame([], (new ExpectExceptionCallInspector())->errorsForCall(
            'expectException',
            new \PhpParser\Node\Expr\ClassConstFetch(
                new \PhpParser\Node\Name('RuntimeException'),
                new \PhpParser\Node\Identifier('class'),
            ),
            1,
            $scope,
        ));
    }

    public function testExpectedObjectClassNameIgnoresNonObjectType(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new StringType());

        self::assertNull((new ExpectExceptionCallInspector())->expectedObjectClassName(
            new \PhpParser\Node\Expr\Variable('error'),
            $scope,
        ));
    }
}
