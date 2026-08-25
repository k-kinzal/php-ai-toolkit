<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpAiToolkit\PhpStan\Rule\Shared\CallMethodNameResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\Shared\CallMethodNameResolver
 */
#[CoversClass(CallMethodNameResolver::class)]
final class CallMethodNameResolverTest extends TestCase
{
    public function testResolveReturnsIdentifierName(): void
    {
        self::assertSame('run', (new CallMethodNameResolver())->resolve(new \PhpParser\Node\Identifier('run')));
    }

    public function testResolveReturnsNullForDynamicExpression(): void
    {
        self::assertNull((new CallMethodNameResolver())->resolve(new \PhpParser\Node\Expr\Variable('method')));
    }
}
