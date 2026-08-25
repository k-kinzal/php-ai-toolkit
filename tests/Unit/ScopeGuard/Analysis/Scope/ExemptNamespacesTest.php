<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Scope;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\Scope\ExemptNamespaces;
use Toolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;

/**
 * @covers \Toolkit\ScopeGuard\Analysis\Scope\ExemptNamespaces
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\NamespaceLineage
 */
#[CoversClass(ExemptNamespaces::class)]
#[UsesClass(NamespaceLineage::class)]
final class ExemptNamespacesTest extends TestCase
{
    public function testContainsAcceptsExactPrefix(): void
    {
        self::assertTrue((new ExemptNamespaces(['Tests']))->contains('Tests'));
    }

    public function testContainsAcceptsSubNamespaceOfPrefix(): void
    {
        self::assertTrue((new ExemptNamespaces(['Tests']))->contains('Tests\\Unit\\Domain'));
    }

    public function testContainsRejectsUnlistedNamespace(): void
    {
        self::assertFalse((new ExemptNamespaces(['Tests']))->contains('App\\Domain'));
    }

    public function testContainsRejectsEverythingWithoutPrefixes(): void
    {
        self::assertFalse((new ExemptNamespaces())->contains('App\\Domain'));
    }

    public function testContainsIgnoresEmptyPrefix(): void
    {
        self::assertFalse((new ExemptNamespaces(['']))->contains('App\\Domain'));
    }
}
