<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Nop;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\TestClass\NestedScopeFilter;

/**
 * @covers \Toolkit\PhpStan\Rule\TestClass\NestedScopeFilter
 */
#[CoversClass(NestedScopeFilter::class)]
final class NestedScopeFilterTest extends TestCase
{
    public function testFilterRemovesViolationsInsideNestedScopes(): void
    {
        $violation = new If_(
            new ConstFetch(new Name('true')),
            [],
            ['startLine' => 3, 'endLine' => 3],
        );
        $closure = new Closure(['stmts' => [$violation]], ['startLine' => 2, 'endLine' => 4]);

        self::assertSame([], (new NestedScopeFilter())->filter([$violation], [new Expression($closure)]));
    }

    public function testContainsReturnsTrueForNodeInsideScope(): void
    {
        $node = new Nop(['startLine' => 3, 'endLine' => 3]);
        $closure = new Closure([], ['startLine' => 2, 'endLine' => 4]);

        self::assertTrue((new NestedScopeFilter())->contains($node, [$closure]));
    }
}
