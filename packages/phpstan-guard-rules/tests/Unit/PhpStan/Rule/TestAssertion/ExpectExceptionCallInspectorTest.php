<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestAssertion;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\Shared\CallArgumentResolver;
use Toolkit\PhpStan\Rule\Shared\CallMethodNameResolver;
use Toolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver;
use Toolkit\PhpStan\Rule\Shared\PhpUnitCallTargetMatcher;
use Toolkit\PhpStan\Rule\TestAssertion\BrokenCodeExceptionClassifier;
use Toolkit\PhpStan\Rule\TestAssertion\ExpectExceptionCallInspector;
use Toolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationErrorBuilder;

/**
 * @covers \Toolkit\PhpStan\Rule\TestAssertion\ExpectExceptionCallInspector
 * @uses \Toolkit\PhpStan\Rule\TestAssertion\BrokenCodeExceptionClassifier
 * @uses \Toolkit\PhpStan\Rule\Shared\CallArgumentResolver
 * @uses \Toolkit\PhpStan\Rule\Shared\CallMethodNameResolver
 * @uses \Toolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver
 * @uses \Toolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\Shared\PhpUnitCallTargetMatcher
 * @medium
 */
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
        $call = new MethodCall(
            new Variable('this'),
            'expectException',
            [new Arg(new ClassConstFetch(
                new Name('LogicException'),
                new Identifier('class'),
            ))],
        );

        self::assertCount(1, (new ExpectExceptionCallInspector())->errors($call, $scope));
    }

    public function testErrorsReportsStaticExpectException(): void
    {
        self::createReflectionProvider();
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('InvalidArgumentException');
        $call = new StaticCall(
            new Name('self'),
            'expectException',
            [new Arg(new ClassConstFetch(
                new Name('InvalidArgumentException'),
                new Identifier('class'),
            ))],
        );

        self::assertCount(1, (new ExpectExceptionCallInspector())->errors($call, $scope));
    }

    public function testErrorsReportsExpectExceptionObject(): void
    {
        self::createReflectionProvider();
        $scope = self::createStub(Scope::class);
        $scope->method('getType')->willReturn(new ObjectType('TypeError'));
        $call = new MethodCall(
            new Variable('this'),
            'expectExceptionObject',
            [new Arg(new Variable('error'))],
        );

        self::assertCount(1, (new ExpectExceptionCallInspector())->errors($call, $scope));
    }

    public function testErrorsIgnoresCallOnAnotherObject(): void
    {
        $call = new MethodCall(
            new Variable('runner'),
            'expectException',
            [new Arg(new ClassConstFetch(
                new Name('LogicException'),
                new Identifier('class'),
            ))],
        );

        self::assertSame([], (new ExpectExceptionCallInspector())->errors($call, self::createStub(Scope::class)));
    }

    public function testErrorsIgnoresStaticCallOnAnotherClass(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\\ReportRunner');
        $call = new StaticCall(
            new Name('ReportRunner'),
            'expectException',
            [new Arg(new ClassConstFetch(
                new Name('LogicException'),
                new Identifier('class'),
            ))],
        );

        self::assertSame([], (new ExpectExceptionCallInspector())->errors($call, $scope));
    }

    public function testErrorsIgnoresOtherExpressions(): void
    {
        self::assertSame([], (new ExpectExceptionCallInspector())->errors(
            new Variable('report'),
            self::createStub(Scope::class),
        ));
    }

    public function testErrorsForCallIgnoresOtherMethod(): void
    {
        self::assertSame([], (new ExpectExceptionCallInspector())->errorsForCall(
            'expectExceptionMessage',
            new String_('Report source is unreadable.'),
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
            new ClassConstFetch(
                new Name('RuntimeException'),
                new Identifier('class'),
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
            new Variable('error'),
            $scope,
        ));
    }
}
