<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility\Scope;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScope;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScopeResolver;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityTagParser;

/**
 * @covers \Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScopeResolver
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScope
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityTagParser
 */
#[CoversClass(VisibilityScopeResolver::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(VisibilityScope::class)]
#[UsesClass(VisibilityTagParser::class)]
final class VisibilityScopeResolverTest extends TestCase
{
    public function testResolveSupportsKeywordsNamedNamespacesAndUnions(): void
    {
        $scope = (new VisibilityScopeResolver())->resolve(
            "/**\n * @visibility namespace\n * @visibility App\\Console\n */",
            'App\Domain',
        );

        self::assertTrue($scope->permits('App\Domain'));
        self::assertTrue($scope->permits('App\Console\Command'));
        self::assertFalse($scope->permits('Other'));
    }

    public function testResolveTreatsPublicAsUnrestricted(): void
    {
        self::assertTrue((new VisibilityScopeResolver())->resolve('/** @visibility public */', 'App\Domain')->permits('Other'));
    }

    public function testNamespaceForResolvesParentKeyword(): void
    {
        self::assertSame('App', (new VisibilityScopeResolver())->namespaceFor('parent', 'App\Domain'));
    }

    public function testKeywordOfIsCaseInsensitive(): void
    {
        self::assertSame('namespace', (new VisibilityScopeResolver())->keywordOf('Namespace'));
    }

    public function testKeywordsOfFiltersNamedNamespaces(): void
    {
        self::assertSame(['root'], (new VisibilityScopeResolver())->keywordsOf(['App\Domain', 'root']));
    }

    public function testIsKeywordShapeAcceptsBareLowercaseWord(): void
    {
        self::assertTrue((new VisibilityScopeResolver())->isKeywordShape('parrent'));
    }

    public function testIsNamespaceNameValidatesEverySegment(): void
    {
        self::assertTrue((new VisibilityScopeResolver())->isNamespaceName('App\Domain'));
        self::assertFalse((new VisibilityScopeResolver())->isNamespaceName('123bad'));
    }

    public function testCollapseDropsCoveredNamespace(): void
    {
        self::assertSame(['App'], (new VisibilityScopeResolver())->collapse(['App', 'App\Domain']));
    }
}
