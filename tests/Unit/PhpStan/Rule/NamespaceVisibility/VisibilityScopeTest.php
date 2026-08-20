<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\NamespaceVisibility;

use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\NamespaceLineage;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(VisibilityScope::class)]
#[UsesClass(NamespaceLineage::class)]
final class VisibilityScopeTest extends TestCase
{
    public function testIsRestrictedReportsDeclaredNarrowing(): void
    {
        self::assertTrue((new VisibilityScope(['App\\Domain'], ['namespace'], true))->isRestricted());
    }

    public function testIsRestrictedReportsUntaggedDeclaration(): void
    {
        self::assertFalse((new VisibilityScope([], [], false))->isRestricted());
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

    public function testAllowedNamespacesReturnsResolvedScopes(): void
    {
        self::assertSame(['App\\Domain'], (new VisibilityScope(['App\\Domain'], ['namespace'], true))->allowedNamespaces());
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
}
