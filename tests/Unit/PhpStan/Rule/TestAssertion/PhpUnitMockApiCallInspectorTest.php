<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestAssertion;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\CallArgumentResolver;
use Toolkit\PhpStan\Rule\Shared\CallMethodNameResolver;
use Toolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver;
use Toolkit\PhpStan\Rule\Shared\PhpUnitCallTargetMatcher;
use Toolkit\PhpStan\Rule\TestAssertion\PhpUnitMockApiCallInspector;
use Toolkit\PhpStan\Rule\TestAssertion\PhpUnitMockApiErrorBuilder;
use Toolkit\PhpStan\Rule\TestAssertion\PhpUnitMockApiMethodPolicy;

/**
 * @covers \Toolkit\PhpStan\Rule\TestAssertion\PhpUnitMockApiCallInspector
 * @uses \Toolkit\PhpStan\Rule\Shared\CallArgumentResolver
 * @uses \Toolkit\PhpStan\Rule\Shared\CallMethodNameResolver
 * @uses \Toolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver
 * @uses \Toolkit\PhpStan\Rule\Shared\PhpUnitCallTargetMatcher
 * @uses \Toolkit\PhpStan\Rule\TestAssertion\PhpUnitMockApiErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\TestAssertion\PhpUnitMockApiMethodPolicy
 */
#[CoversClass(PhpUnitMockApiCallInspector::class)]
#[UsesClass(CallArgumentResolver::class)]
#[UsesClass(CallMethodNameResolver::class)]
#[UsesClass(ClassStringExpressionResolver::class)]
#[UsesClass(PhpUnitCallTargetMatcher::class)]
#[UsesClass(PhpUnitMockApiErrorBuilder::class)]
#[UsesClass(PhpUnitMockApiMethodPolicy::class)]
final class PhpUnitMockApiCallInspectorTest extends TestCase
{
    public function testErrorsReturnsMockApiError(): void
    {
        $call = new \PhpParser\Node\Expr\MethodCall(new \PhpParser\Node\Expr\Variable('this'), 'getMockBuilder');

        self::assertCount(1, (new PhpUnitMockApiCallInspector(self::createStub(ReflectionProvider::class)))->errors($call, self::createStub(Scope::class)));
    }

    public function testErrorsForCallReturnsEmptyForUnrelatedMethod(): void
    {
        self::assertSame([], (new PhpUnitMockApiCallInspector(self::createStub(ReflectionProvider::class)))->errorsForCall(
            'assertSame',
            null,
            1,
            self::createStub(Scope::class),
        ));
    }
}
