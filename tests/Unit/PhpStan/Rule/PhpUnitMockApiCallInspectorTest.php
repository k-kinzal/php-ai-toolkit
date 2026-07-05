<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\CallArgumentResolver;
use PhpAiToolkit\PhpStan\Rule\CallMethodNameResolver;
use PhpAiToolkit\PhpStan\Rule\ClassStringExpressionResolver;
use PhpAiToolkit\PhpStan\Rule\PhpUnitCallTargetMatcher;
use PhpAiToolkit\PhpStan\Rule\PhpUnitMockApiCallInspector;
use PhpAiToolkit\PhpStan\Rule\PhpUnitMockApiErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\PhpUnitMockApiMethodPolicy;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
