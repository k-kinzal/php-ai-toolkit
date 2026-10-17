<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\If_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\TestClass\ControlFlowTypeResolver;

/**
 * @covers \Toolkit\PhpStan\Rule\TestClass\ControlFlowTypeResolver
 */
#[CoversClass(ControlFlowTypeResolver::class)]
final class ControlFlowTypeResolverTest extends TestCase
{
    public function testTypeReturnsControlFlowLabel(): void
    {
        $if = new If_(new ConstFetch(new Name('true')));

        self::assertSame('if', (new ControlFlowTypeResolver())->type($if));
    }
}
