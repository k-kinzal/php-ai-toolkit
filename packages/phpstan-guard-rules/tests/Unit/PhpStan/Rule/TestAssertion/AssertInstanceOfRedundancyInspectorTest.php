<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestAssertion;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\CallArgumentResolver;
use Toolkit\PhpStan\Rule\Shared\CallMethodNameResolver;
use Toolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver;
use Toolkit\PhpStan\Rule\Shared\PhpUnitCallTargetMatcher;
use Toolkit\PhpStan\Rule\TestAssertion\AssertInstanceOfRedundancyInspector;
use Toolkit\PhpStan\Rule\TestAssertion\AssertInstanceOfTypeMatcher;
use Toolkit\PhpStan\Rule\TestAssertion\NoRedundantAssertInstanceOfErrorBuilder;

/**
 * @covers \Toolkit\PhpStan\Rule\TestAssertion\AssertInstanceOfRedundancyInspector
 * @uses \Toolkit\PhpStan\Rule\Shared\CallArgumentResolver
 * @uses \Toolkit\PhpStan\Rule\Shared\CallMethodNameResolver
 * @uses \Toolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver
 * @uses \Toolkit\PhpStan\Rule\TestAssertion\AssertInstanceOfTypeMatcher
 * @uses \Toolkit\PhpStan\Rule\TestAssertion\NoRedundantAssertInstanceOfErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\Shared\PhpUnitCallTargetMatcher
 */
#[CoversClass(AssertInstanceOfRedundancyInspector::class)]
#[UsesClass(CallArgumentResolver::class)]
#[UsesClass(CallMethodNameResolver::class)]
#[UsesClass(ClassStringExpressionResolver::class)]
#[UsesClass(AssertInstanceOfTypeMatcher::class)]
#[UsesClass(NoRedundantAssertInstanceOfErrorBuilder::class)]
#[UsesClass(PhpUnitCallTargetMatcher::class)]
final class AssertInstanceOfRedundancyInspectorTest extends TestCase
{
    public function testErrorsReturnsRedundantAssertInstanceOfError(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\\Service');
        $scope->method('getType')->willReturn(new ObjectType('App\\Service'));
        $expected = new ClassConstFetch(
            new Name('Service'),
            new Identifier('class'),
        );
        $actual = new Variable('actual');
        $call = new MethodCall(
            new Variable('this'),
            'assertInstanceOf',
            [new Arg($expected), new Arg($actual)],
        );

        self::assertCount(1, (new AssertInstanceOfRedundancyInspector())->errors($call, $scope));
    }

    public function testErrorsForCallReturnsEmptyForOtherMethod(): void
    {
        self::assertSame([], (new AssertInstanceOfRedundancyInspector())->errorsForCall(
            'assertSame',
            [],
            1,
            self::createStub(Scope::class),
        ));
    }
}
