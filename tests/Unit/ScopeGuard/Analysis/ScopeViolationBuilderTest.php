<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\Declaration\Declaration;
use Toolkit\ScopeGuard\Analysis\Reference\Reference;
use Toolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use Toolkit\ScopeGuard\Analysis\Scope\VisibilityScope;
use Toolkit\ScopeGuard\Analysis\ScopeViolationBuilder;
use Toolkit\ScopeGuard\Analysis\Violation;

/**
 * @covers \Toolkit\ScopeGuard\Analysis\ScopeViolationBuilder
 * @uses \Toolkit\ScopeGuard\Analysis\Declaration\Declaration
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\NamespaceLineage
 * @uses \Toolkit\ScopeGuard\Analysis\Reference\Reference
 * @uses \Toolkit\ScopeGuard\Analysis\Scope\VisibilityScope
 * @uses \Toolkit\ScopeGuard\Analysis\Violation
 */
#[CoversClass(ScopeViolationBuilder::class)]
#[UsesClass(Declaration::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(Reference::class)]
#[UsesClass(VisibilityScope::class)]
#[UsesClass(Violation::class)]
final class ScopeViolationBuilderTest extends TestCase
{
    /**
     * @dataProvider providerScopedClassAndReference
     */
    #[DataProvider('providerScopedClassAndReference')]
    public function testOutOfScopeExplainsTheScopeAndTheFix(Declaration $declaration, Reference $reference): void
    {
        self::assertSame(
            'Class App\\Domain\\Order is not visible from namespace "App\\Http": the declaration is marked "@visibility namespace", so it may only be named from namespace "App\\Domain" and its sub-namespaces. Move this instantiation into that namespace, or widen the declaration to "@visibility App".',
            (new ScopeViolationBuilder())->outOfScope($declaration, $reference)->message
        );
    }

    /**
     * @dataProvider providerScopedClassAndReference
     */
    #[DataProvider('providerScopedClassAndReference')]
    public function testOutOfScopeReportsTheReferenceLocation(Declaration $declaration, Reference $reference): void
    {
        self::assertSame(21, (new ScopeViolationBuilder())->outOfScope($declaration, $reference)->line);
    }

    /**
     * @dataProvider providerScopedClassAndReference
     */
    #[DataProvider('providerScopedClassAndReference')]
    public function testOutOfScopeUsesTheOutOfScopeRule(Declaration $declaration, Reference $reference): void
    {
        self::assertSame('out_of_scope', (new ScopeViolationBuilder())->outOfScope($declaration, $reference)->rule);
    }

    /**
     * @dataProvider providerScopedClass
     */
    #[DataProvider('providerScopedClass')]
    public function testInvalidScopeQuotesTheTagAndTheReason(Declaration $declaration): void
    {
        self::assertSame(
            'Fix "@visibility parrent" on class App\\Domain\\Order: it names nothing.',
            (new ScopeViolationBuilder())->invalidScope($declaration, 'parrent', 'it names nothing')->message
        );
    }

    /**
     * @dataProvider providerScopedClass
     */
    #[DataProvider('providerScopedClass')]
    public function testInvalidScopeReportsTheDeclarationLocation(Declaration $declaration): void
    {
        self::assertSame(11, (new ScopeViolationBuilder())->invalidScope($declaration, 'parrent', 'it names nothing')->line);
    }

    /**
     * @dataProvider providerScopedClass
     */
    #[DataProvider('providerScopedClass')]
    public function testContradictoryScopesExplainsWhichTagToRemove(Declaration $declaration): void
    {
        self::assertSame(
            'Remove either "@visibility public" or the narrowing @visibility tags on class App\\Domain\\Order: "public" makes the declaration visible everywhere, so keeping both leaves the narrower tags with no effect.',
            (new ScopeViolationBuilder())->contradictoryScopes($declaration)->message
        );
    }

    public function testDescribeNamespaceNamesTheGlobalNamespace(): void
    {
        self::assertSame('the global namespace', (new ScopeViolationBuilder())->describeNamespace(''));
    }

    public function testDescribeNamespaceQuotesANamedNamespace(): void
    {
        self::assertSame('namespace "App\\Domain"', (new ScopeViolationBuilder())->describeNamespace('App\\Domain'));
    }

    public function testWideningForNamesTheSharedAncestor(): void
    {
        self::assertSame('App', (new ScopeViolationBuilder())->wideningFor('App\\Domain', 'App\\Http'));
    }

    public function testWideningForFallsBackToPublic(): void
    {
        self::assertSame('public', (new ScopeViolationBuilder())->wideningFor('App\\Domain', 'Other\\Place'));
    }

    /**
     * @return array<string, array{Declaration}>
     */
    public static function providerScopedClass(): array
    {
        return ['a namespace scoped class' => [new Declaration(
            'App\\Domain\\Order',
            'class',
            'App\\Domain',
            new VisibilityScope(['App\\Domain'], ['namespace'], true),
            'src/Domain/Order.php',
            11,
        )]];
    }

    /**
     * @return array<string, array{Declaration, Reference}>
     */
    public static function providerScopedClassAndReference(): array
    {
        return ['a namespace scoped class named from another namespace' => [
            new Declaration(
                'App\\Domain\\Order',
                'class',
                'App\\Domain',
                new VisibilityScope(['App\\Domain'], ['namespace'], true),
                'src/Domain/Order.php',
                11,
            ),
            new Reference('App\\Domain\\Order', '__construct', 'instantiation', 'App\\Http', 'src/Http/Controller.php', 21),
        ]];
    }
}
