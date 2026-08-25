<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Parse;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\Parse\FileNamespaces;
use Toolkit\ScopeGuard\Analysis\Parse\NodeWalker;

/**
 * @covers \Toolkit\ScopeGuard\Analysis\Parse\FileNamespaces
 * @uses \Toolkit\ScopeGuard\Analysis\Parse\NodeWalker
 */
#[CoversClass(FileNamespaces::class)]
#[UsesClass(NodeWalker::class)]
final class FileNamespacesTest extends TestCase
{
    public function testGroupsKeysNodesByDeclaredNamespace(): void
    {
        $namespace = new \PhpParser\Node\Stmt\Namespace_(new \PhpParser\Node\Name('App\\Domain'), [new \PhpParser\Node\Stmt\Class_('Order')]);

        self::assertSame(['App\\Domain'], array_keys((new FileNamespaces())->groups([$namespace])));
    }

    public function testGroupsMergesRepeatedNamespaceBlocks(): void
    {
        $first = new \PhpParser\Node\Stmt\Namespace_(new \PhpParser\Node\Name('App'), [new \PhpParser\Node\Stmt\Nop()]);
        $second = new \PhpParser\Node\Stmt\Namespace_(new \PhpParser\Node\Name('App'), [new \PhpParser\Node\Stmt\Nop()]);

        self::assertCount(2, (new FileNamespaces())->groups([$first, $second])['App']);
    }

    public function testGroupsReportsTheGlobalNamespaceAsEmptyString(): void
    {
        self::assertSame([''], array_keys((new FileNamespaces())->groups([new \PhpParser\Node\Stmt\Nop()])));
    }

    public function testGroupsReportsAnUnnamedNamespaceBlockAsGlobal(): void
    {
        $namespace = new \PhpParser\Node\Stmt\Namespace_(null, [new \PhpParser\Node\Stmt\Nop()]);

        self::assertSame([''], array_keys((new FileNamespaces())->groups([$namespace])));
    }

    public function testGroupsReturnsNothingForAnEmptyFile(): void
    {
        self::assertSame([], (new FileNamespaces())->groups([]));
    }
}
