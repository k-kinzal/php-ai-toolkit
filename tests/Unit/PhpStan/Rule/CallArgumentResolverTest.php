<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\CallArgumentResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CallArgumentResolver::class)]
final class CallArgumentResolverTest extends TestCase
{
    public function testValueAtReturnsArgumentExpression(): void
    {
        $expression = new \PhpParser\Node\Expr\Variable('value');

        self::assertSame($expression, (new CallArgumentResolver())->valueAt([new \PhpParser\Node\Arg($expression)], 0));
    }

    public function testFirstValueReturnsFirstArgumentExpression(): void
    {
        $expression = new \PhpParser\Node\Expr\Variable('value');

        self::assertSame($expression, (new CallArgumentResolver())->firstValue([new \PhpParser\Node\Arg($expression)]));
    }
}
