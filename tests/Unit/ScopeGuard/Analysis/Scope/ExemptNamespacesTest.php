<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Scope;

use PhpAiToolkit\ScopeGuard\Analysis\Scope\ExemptNamespaces;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
