<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver;

/**
 * @covers \Toolkit\PhpStan\Rule\Shared\ClassStringExpressionResolver
 */
#[CoversClass(ClassStringExpressionResolver::class)]
final class ClassStringExpressionResolverTest extends TestCase
{
    public function testResolveReturnsResolvedClassName(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('resolveName')->willReturn('App\\Service');
        $expression = new ClassConstFetch(
            new Name('Service'),
            new Identifier('class'),
        );

        self::assertSame('App\\Service', (new ClassStringExpressionResolver())->resolve($expression, $scope));
    }

    public function testResolveReturnsNullForNonClassStringExpression(): void
    {
        self::assertNull((new ClassStringExpressionResolver())->resolve(
            new Variable('className'),
            self::createStub(Scope::class),
        ));
    }
}
