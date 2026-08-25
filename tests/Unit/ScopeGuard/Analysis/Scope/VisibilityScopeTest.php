<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Scope;

use PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScope
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage
 */
#[CoversClass(VisibilityScope::class)]
#[UsesClass(NamespaceLineage::class)]
final class VisibilityScopeTest extends TestCase
{
    public function testAllowedNamespacesIsReadable(): void
    {
        self::assertSame(['App\\Domain'], (new VisibilityScope(['App\\Domain'], ['namespace'], true))->allowedNamespaces);
    }

    public function testDeclaredValuesIsReadable(): void
    {
        self::assertSame(['namespace'], (new VisibilityScope(['App\\Domain'], ['namespace'], true))->declaredValues);
    }

    public function testRestrictedIsReadable(): void
    {
        self::assertTrue((new VisibilityScope(['App\\Domain'], ['namespace'], true))->restricted);
    }


    public function testPermitsAcceptsEveryNamespaceWithoutRestriction(): void
    {
        self::assertTrue((new VisibilityScope([], [], false))->permits('Other\\Place'));
    }

    public function testPermitsAcceptsAllowedSubNamespace(): void
    {
        self::assertTrue((new VisibilityScope(['App\\Domain'], ['namespace'], true))->permits('App\\Domain\\Order'));
    }

    public function testPermitsAcceptsSecondAllowedNamespace(): void
    {
        self::assertTrue((new VisibilityScope(['App\\Domain', 'App\\Http'], ['App\\Http'], true))->permits('App\\Http'));
    }

    public function testPermitsRejectsNamespaceOutsideEveryScope(): void
    {
        self::assertFalse((new VisibilityScope(['App\\Domain'], ['namespace'], true))->permits('Other\\Place'));
    }

    public function testDescribeTagsQuotesSingleTag(): void
    {
        self::assertSame('"@visibility namespace"', (new VisibilityScope(['App\\Domain'], ['namespace'], true))->describeTags());
    }

    public function testDescribeTagsJoinsEveryTag(): void
    {
        self::assertSame(
            '"@visibility root" and "@visibility App\\Http"',
            (new VisibilityScope(['App'], ['root', 'App\\Http'], true))->describeTags()
        );
    }

    public function testDescribeAllowedNamesOneNamespace(): void
    {
        self::assertSame(
            'namespace "App\\Domain" and its sub-namespaces',
            (new VisibilityScope(['App\\Domain'], ['namespace'], true))->describeAllowed()
        );
    }

    public function testDescribeAllowedJoinsSeveralNamespaces(): void
    {
        self::assertSame(
            'namespaces "App\\Domain", "App\\Http" and their sub-namespaces',
            (new VisibilityScope(['App\\Domain', 'App\\Http'], ['namespace'], true))->describeAllowed()
        );
    }
}
