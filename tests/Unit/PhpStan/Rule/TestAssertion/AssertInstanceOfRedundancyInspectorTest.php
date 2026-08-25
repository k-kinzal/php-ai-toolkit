<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestAssertion;

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
        $expected = new \PhpParser\Node\Expr\ClassConstFetch(
            new \PhpParser\Node\Name('Service'),
            new \PhpParser\Node\Identifier('class'),
        );
        $actual = new \PhpParser\Node\Expr\Variable('actual');
        $call = new \PhpParser\Node\Expr\MethodCall(
            new \PhpParser\Node\Expr\Variable('this'),
            'assertInstanceOf',
            [new \PhpParser\Node\Arg($expected), new \PhpParser\Node\Arg($actual)],
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
