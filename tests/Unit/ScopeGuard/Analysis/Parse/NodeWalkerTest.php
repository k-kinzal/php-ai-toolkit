<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Parse;

use PhpAiToolkit\ScopeGuard\Analysis\Parse\NodeWalker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Analysis\Parse\NodeWalker
 */
#[CoversClass(NodeWalker::class)]
final class NodeWalkerTest extends TestCase
{
    public function testWalkReturnsTheGivenNode(): void
    {
        self::assertCount(1, (new NodeWalker())->walk([new \PhpParser\Node\Name('Order')]));
    }

    public function testWalkDescendsIntoChildNodes(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Order', ['extends' => new \PhpParser\Node\Name('Base')]);

        self::assertCount(3, (new NodeWalker())->walk([$class]));
    }

    public function testWalkFlattensNestedArrays(): void
    {
        self::assertCount(2, (new NodeWalker())->walk([[new \PhpParser\Node\Name('One')], new \PhpParser\Node\Name('Two')]));
    }

    public function testWalkIgnoresValuesThatAreNotNodes(): void
    {
        self::assertSame([], (new NodeWalker())->walk([]));
    }

    public function testChildrenReturnsOneEntryPerSubNode(): void
    {
        $namespace = new \PhpParser\Node\Stmt\Namespace_(new \PhpParser\Node\Name('App'), []);

        self::assertCount(2, (new NodeWalker())->children($namespace));
    }

    public function testChildrenReturnsNothingForALeafNode(): void
    {
        self::assertSame([], (new NodeWalker())->children(new \PhpParser\Node\Stmt\Nop()));
    }
}
