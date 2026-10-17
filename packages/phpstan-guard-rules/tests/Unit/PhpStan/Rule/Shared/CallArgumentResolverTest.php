<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\CallArgumentResolver;

/**
 * @covers \Toolkit\PhpStan\Rule\Shared\CallArgumentResolver
 */
#[CoversClass(CallArgumentResolver::class)]
final class CallArgumentResolverTest extends TestCase
{
    public function testValueAtReturnsArgumentExpression(): void
    {
        $expression = new Variable('value');

        self::assertSame($expression, (new CallArgumentResolver())->valueAt([new Arg($expression)], 0));
    }

    public function testFirstValueReturnsFirstArgumentExpression(): void
    {
        $expression = new Variable('value');

        self::assertSame($expression, (new CallArgumentResolver())->firstValue([new Arg($expression)]));
    }
}
