<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ExceptionHandling;

use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\NodeTraverser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ExceptionHandling\CatchThrowVisitor;
use Toolkit\PhpStan\Rule\Shared\ThrownExpression;

/**
 * @covers \Toolkit\PhpStan\Rule\ExceptionHandling\CatchThrowVisitor
 * @uses \Toolkit\PhpStan\Rule\Shared\ThrownExpression
 */
#[CoversClass(CatchThrowVisitor::class)]
#[UsesClass(ThrownExpression::class)]
final class CatchThrowVisitorTest extends TestCase
{
    public function testEnterNodeCollectsThrowStatements(): void
    {
        $visitor = new CatchThrowVisitor();
        $throw = new Throw_(new New_(new Name('DomainException')));

        $visitor->enterNode($throw);

        self::assertSame([$throw], $visitor->throws());
    }

    public function testEnterNodeSkipsNestedScopesAndTryBlocks(): void
    {
        $visitor = new CatchThrowVisitor();

        self::assertSame(NodeTraverser::DONT_TRAVERSE_CHILDREN, $visitor->enterNode(new Closure()));
        self::assertSame(NodeTraverser::DONT_TRAVERSE_CHILDREN, $visitor->enterNode(new TryCatch([], [], null)));
    }

    public function testThrowsReturnsEmptyListWithoutThrowStatements(): void
    {
        self::assertSame([], (new CatchThrowVisitor())->throws());
    }
}
