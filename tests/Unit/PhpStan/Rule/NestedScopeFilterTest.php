<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\NestedScopeFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NestedScopeFilter::class)]
final class NestedScopeFilterTest extends TestCase
{
    public function testFilterRemovesViolationsInsideNestedScopes(): void
    {
        $violation = new \PhpParser\Node\Stmt\If_(
            new \PhpParser\Node\Expr\ConstFetch(new \PhpParser\Node\Name('true')),
            [],
            ['startLine' => 3, 'endLine' => 3],
        );
        $closure = new \PhpParser\Node\Expr\Closure(['stmts' => [$violation]], ['startLine' => 2, 'endLine' => 4]);

        self::assertSame([], (new NestedScopeFilter())->filter([$violation], [new \PhpParser\Node\Stmt\Expression($closure)]));
    }

    public function testContainsReturnsTrueForNodeInsideScope(): void
    {
        $node = new \PhpParser\Node\Stmt\Nop(['startLine' => 3, 'endLine' => 3]);
        $closure = new \PhpParser\Node\Expr\Closure([], ['startLine' => 2, 'endLine' => 4]);

        self::assertTrue((new NestedScopeFilter())->contains($node, [$closure]));
    }
}
