<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpAiToolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver
 */
#[CoversClass(ClassStringExpressionResolver::class)]
final class ClassStringExpressionResolverTest extends TestCase
{
    public function testResolveReturnsResolvedClassName(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\\Service');
        $expression = new \PhpParser\Node\Expr\ClassConstFetch(
            new \PhpParser\Node\Name('Service'),
            new \PhpParser\Node\Identifier('class'),
        );

        self::assertSame('App\\Service', (new ClassStringExpressionResolver())->resolve($expression, $scope));
    }

    public function testResolveReturnsNullForNonClassStringExpression(): void
    {
        self::assertNull((new ClassStringExpressionResolver())->resolve(
            new \PhpParser\Node\Expr\Variable('className'),
            self::createStub(Scope::class),
        ));
    }
}
