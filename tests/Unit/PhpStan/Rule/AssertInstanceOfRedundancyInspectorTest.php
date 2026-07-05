<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\AssertInstanceOfRedundancyInspector;
use PhpAiToolkit\PhpStan\Rule\CallArgumentResolver;
use PhpAiToolkit\PhpStan\Rule\CallMethodNameResolver;
use PhpAiToolkit\PhpStan\Rule\ClassStringExpressionResolver;
use PhpAiToolkit\PhpStan\Rule\NoRedundantAssertInstanceOfErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\PhpUnitCallTargetMatcher;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssertInstanceOfRedundancyInspector::class)]
#[UsesClass(CallArgumentResolver::class)]
#[UsesClass(CallMethodNameResolver::class)]
#[UsesClass(ClassStringExpressionResolver::class)]
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
