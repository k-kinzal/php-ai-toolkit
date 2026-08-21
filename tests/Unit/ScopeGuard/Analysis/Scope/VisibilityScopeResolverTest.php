<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Scope;

use PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScope;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(VisibilityScopeResolver::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(VisibilityScope::class)]
#[UsesClass(VisibilityTagParser::class)]
final class VisibilityScopeResolverTest extends TestCase
{
    public function testResolveLeavesUntaggedDeclarationUnrestricted(): void
    {
        self::assertFalse((new VisibilityScopeResolver())->resolve(null, 'App\\Domain')->restricted);
    }

    public function testResolveLeavesPublicDeclarationUnrestricted(): void
    {
        self::assertFalse((new VisibilityScopeResolver())->resolve('/** @visibility public */', 'App\\Domain')->restricted);
    }

    public function testResolveMarksATaggedDeclarationRestricted(): void
    {
        self::assertTrue((new VisibilityScopeResolver())->resolve('/** @visibility namespace */', 'App\\Domain')->restricted);
    }

    public function testResolveNarrowsToDeclaringNamespace(): void
    {
        self::assertSame(
            ['App\\Domain'],
            (new VisibilityScopeResolver())->resolve('/** @visibility namespace */', 'App\\Domain')->allowedNamespaces
        );
    }

    public function testResolveNarrowsToParentNamespace(): void
    {
        self::assertSame(
            ['App\\Domain'],
            (new VisibilityScopeResolver())->resolve('/** @visibility parent */', 'App\\Domain\\Order')->allowedNamespaces
        );
    }

    public function testResolveNarrowsToRootNamespace(): void
    {
        self::assertSame(
            ['App'],
            (new VisibilityScopeResolver())->resolve('/** @visibility root */', 'App\\Domain\\Order')->allowedNamespaces
        );
    }

    public function testResolveKeepsDeclaringNamespaceBesideUnrelatedScope(): void
    {
        self::assertSame(
            ['Other\\Place', 'App\\Domain'],
            (new VisibilityScopeResolver())->resolve('/** @visibility Other\\Place */', 'App\\Domain')->allowedNamespaces
        );
    }

    public function testResolveReadsNamespaceBehindLeadingSeparator(): void
    {
        self::assertSame(
            ['root', 'App\\Domain'],
            (new VisibilityScopeResolver())->resolve('/** @visibility \\root */', 'App\\Domain')->allowedNamespaces
        );
    }

    public function testResolveLeavesMistypedKeywordUnrestricted(): void
    {
        self::assertFalse((new VisibilityScopeResolver())->resolve('/** @visibility parrent */', 'App\\Domain')->restricted);
    }

    public function testResolveLeavesUnusableScopeUnrestricted(): void
    {
        self::assertFalse((new VisibilityScopeResolver())->resolve('/** @visibility 123bad */', 'App\\Domain')->restricted);
    }

    public function testResolveIgnoresDeclaringNamespaceOfGlobalDeclaration(): void
    {
        self::assertSame(
            ['App\\Domain'],
            (new VisibilityScopeResolver())->resolve('/** @visibility App\\Domain */', '')->allowedNamespaces
        );
    }

    public function testNamespaceForResolvesKeywordAgainstDeclaringNamespace(): void
    {
        self::assertSame('App', (new VisibilityScopeResolver())->namespaceFor('parent', 'App\\Domain'));
    }

    public function testNamespaceForResolvesRootKeyword(): void
    {
        self::assertSame('App', (new VisibilityScopeResolver())->namespaceFor('root', 'App\\Domain\\Order'));
    }

    public function testNamespaceForRejectsPublicKeyword(): void
    {
        self::assertNull((new VisibilityScopeResolver())->namespaceFor('public', 'App\\Domain'));
    }

    public function testKeywordOfRecognisesScopeKeyword(): void
    {
        self::assertSame('root', (new VisibilityScopeResolver())->keywordOf('Root'));
    }

    public function testKeywordOfIgnoresNamespaceBehindLeadingSeparator(): void
    {
        self::assertNull((new VisibilityScopeResolver())->keywordOf('\\root'));
    }

    public function testKeywordsOfReturnsOnlyKeywords(): void
    {
        self::assertSame(['public'], (new VisibilityScopeResolver())->keywordsOf(['public', 'App\\Domain']));
    }

    public function testIsKeywordShapeAcceptsBareLowercaseWord(): void
    {
        self::assertTrue((new VisibilityScopeResolver())->isKeywordShape('parrent'));
    }

    public function testIsKeywordShapeRejectsQualifiedNamespace(): void
    {
        self::assertFalse((new VisibilityScopeResolver())->isKeywordShape('app\\domain'));
    }

    public function testIsKeywordShapeRejectsCapitalisedNamespace(): void
    {
        self::assertFalse((new VisibilityScopeResolver())->isKeywordShape('App'));
    }

    public function testIsNamespaceNameAcceptsQualifiedName(): void
    {
        self::assertTrue((new VisibilityScopeResolver())->isNamespaceName('App\\Domain_1'));
    }

    public function testIsNamespaceNameRejectsLeadingDigit(): void
    {
        self::assertFalse((new VisibilityScopeResolver())->isNamespaceName('123bad'));
    }

    public function testIsNamespaceNameRejectsTrailingJunk(): void
    {
        self::assertFalse((new VisibilityScopeResolver())->isNamespaceName('App\\Domain!'));
    }

    public function testCollapseDropsNamespaceCoveredByAnother(): void
    {
        self::assertSame(['App'], (new VisibilityScopeResolver())->collapse(['App', 'App\\Domain']));
    }

    public function testCollapseDropsRepeatedNamespace(): void
    {
        self::assertSame(['App\\Domain'], (new VisibilityScopeResolver())->collapse(['App\\Domain', 'App\\Domain']));
    }
}
