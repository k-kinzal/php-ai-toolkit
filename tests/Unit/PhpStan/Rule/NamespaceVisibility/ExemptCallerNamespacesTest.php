<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\NamespaceVisibility;

use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\ExemptCallerNamespaces;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\NamespaceLineage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExemptCallerNamespaces::class)]
#[UsesClass(NamespaceLineage::class)]
final class ExemptCallerNamespacesTest extends TestCase
{
    public function testContainsAcceptsExactPrefix(): void
    {
        self::assertTrue((new ExemptCallerNamespaces(['Tests']))->contains('Tests'));
    }

    public function testContainsAcceptsSubNamespaceOfPrefix(): void
    {
        self::assertTrue((new ExemptCallerNamespaces(['Tests']))->contains('Tests\\Unit\\Domain'));
    }

    public function testContainsRejectsUnlistedNamespace(): void
    {
        self::assertFalse((new ExemptCallerNamespaces(['Tests']))->contains('App\\Domain'));
    }

    public function testContainsRejectsEverythingWithoutPrefixes(): void
    {
        self::assertFalse((new ExemptCallerNamespaces())->contains('App\\Domain'));
    }

    public function testContainsIgnoresEmptyPrefix(): void
    {
        self::assertFalse((new ExemptCallerNamespaces(['']))->contains('App\\Domain'));
    }
}
