<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PhpAiToolkit\PhpStan\Rule\TestClass\ControlFlowTypeResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\TestClass\ControlFlowTypeResolver
 */
#[CoversClass(ControlFlowTypeResolver::class)]
final class ControlFlowTypeResolverTest extends TestCase
{
    public function testTypeReturnsControlFlowLabel(): void
    {
        $if = new \PhpParser\Node\Stmt\If_(new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('true')));

        self::assertSame('if', (new ControlFlowTypeResolver())->type($if));
    }
}
