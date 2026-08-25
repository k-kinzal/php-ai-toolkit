<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Declaration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\Declaration\Declaration;
use Toolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use Toolkit\ScopeGuard\Analysis\Scope\VisibilityScope;

/**
 * @covers \Toolkit\ScopeGuard\Analysis\Declaration\Declaration
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\NamespaceLineage
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\VisibilityScope
 */
#[CoversClass(Declaration::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(VisibilityScope::class)]
final class DeclarationTest extends TestCase
{
    /**
     * @dataProvider providerDeclaration
     */
    #[DataProvider('providerDeclaration')]
    public function testSymbolIsReadable(Declaration $declaration): void
    {
        self::assertSame('App\\Domain\\Order', $declaration->symbol);
    }

    /**
     * @dataProvider providerDeclaration
     */
    #[DataProvider('providerDeclaration')]
    public function testKindIsReadable(Declaration $declaration): void
    {
        self::assertSame('class', $declaration->kind);
    }

    /**
     * @dataProvider providerDeclaration
     */
    #[DataProvider('providerDeclaration')]
    public function testNamespaceIsReadable(Declaration $declaration): void
    {
        self::assertSame('App\\Domain', $declaration->namespace);
    }

    /**
     * @dataProvider providerDeclaration
     */
    #[DataProvider('providerDeclaration')]
    public function testScopeIsReadable(Declaration $declaration): void
    {
        self::assertSame(['App\\Domain'], $declaration->scope->allowedNamespaces);
    }

    /**
     * @dataProvider providerDeclaration
     */
    #[DataProvider('providerDeclaration')]
    public function testPathIsReadable(Declaration $declaration): void
    {
        self::assertSame('src/Domain/Order.php', $declaration->path);
    }

    /**
     * @dataProvider providerDeclaration
     */
    #[DataProvider('providerDeclaration')]
    public function testLineIsReadable(Declaration $declaration): void
    {
        self::assertSame(11, $declaration->line);
    }


    /**
     * @return array<string, array{Declaration}>
     */
    public static function providerDeclaration(): array
    {
        return ['a scoped class declaration' => [new Declaration(
            'App\\Domain\\Order',
            'class',
            'App\\Domain',
            new VisibilityScope(['App\\Domain'], ['namespace'], true),
            'src/Domain/Order.php',
            11,
        )]];
    }
}
