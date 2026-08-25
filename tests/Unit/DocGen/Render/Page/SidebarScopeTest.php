<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PhpAiToolkit\DocGen\Render\Page\SidebarScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Render\Page\SidebarScope
 */
#[CoversClass(SidebarScope::class)]
final class SidebarScopeTest extends TestCase
{
    public function testStoresPackageNamespaceActiveSymbolAndSections(): void
    {
        $scope = new SidebarScope('demo/pkg', 'Demo\Core', 'Demo\Core\Engine', [['id' => 'methods', 'label' => 'Methods']]);

        self::assertSame('demo/pkg', $scope->packageName);
        self::assertSame('Demo\Core', $scope->namespace);
        self::assertSame('Demo\Core\Engine', $scope->activeFqcn);
        self::assertSame([['id' => 'methods', 'label' => 'Methods']], $scope->sections);
    }

    public function testStoresNullScopeForPagesOutsideAnyPackage(): void
    {
        $scope = new SidebarScope(null, null, null, []);

        self::assertNull($scope->packageName);
        self::assertNull($scope->namespace);
        self::assertNull($scope->activeFqcn);
        self::assertSame([], $scope->sections);
    }

    public function testStoresEmptyNamespaceAsGlobalScope(): void
    {
        $scope = new SidebarScope('demo/pkg', '', null, []);

        self::assertSame('demo/pkg', $scope->packageName);
        self::assertSame('', $scope->namespace);
        self::assertNull($scope->activeFqcn);
    }
}
