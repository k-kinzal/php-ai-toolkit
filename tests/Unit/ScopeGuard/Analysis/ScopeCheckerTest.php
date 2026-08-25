<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis;

use PhpAiToolkit\ScopeGuard\Analysis\Declaration\Declaration;
use PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex;
use PhpAiToolkit\ScopeGuard\Analysis\ProjectScan;
use PhpAiToolkit\ScopeGuard\Analysis\Reference\Reference;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\ExemptNamespaces;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\ScopeProblemReader;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScope;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser;
use PhpAiToolkit\ScopeGuard\Analysis\ScopeChecker;
use PhpAiToolkit\ScopeGuard\Analysis\ScopeViolationBuilder;
use PhpAiToolkit\ScopeGuard\Analysis\Violation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Analysis\ScopeChecker
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Declaration\Declaration
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\ExemptNamespaces
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\ProjectScan
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Reference\Reference
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\ScopeProblemReader
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\ScopeViolationBuilder
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScope
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Violation
 */
#[CoversClass(ScopeChecker::class)]
#[UsesClass(Declaration::class)]
#[UsesClass(DeclarationIndex::class)]
#[UsesClass(ExemptNamespaces::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(ProjectScan::class)]
#[UsesClass(Reference::class)]
#[UsesClass(ScopeProblemReader::class)]
#[UsesClass(ScopeViolationBuilder::class)]
#[UsesClass(VisibilityScope::class)]
#[UsesClass(VisibilityScopeResolver::class)]
#[UsesClass(VisibilityTagParser::class)]
#[UsesClass(Violation::class)]
final class ScopeCheckerTest extends TestCase
{
    /**
     * @dataProvider providerScopedProject
     */
    #[DataProvider('providerScopedProject')]
    public function testViolationsReportsReferencesAndTags(ProjectScan $scan): void
    {
        self::assertCount(3, (new ScopeChecker())->violations($scan, new ExemptNamespaces()));
    }

    /**
     * @dataProvider providerScopedProject
     */
    #[DataProvider('providerScopedProject')]
    public function testViolationsSkipsExemptNamespaces(ProjectScan $scan): void
    {
        self::assertCount(2, (new ScopeChecker())->violations($scan, new ExemptNamespaces(['App\\Http'])));
    }

    /**
     * @dataProvider providerScopedProject
     */
    #[DataProvider('providerScopedProject')]
    public function testScopeDeclarationViolationsReportsEveryUnusableTag(ProjectScan $scan): void
    {
        self::assertCount(2, (new ScopeChecker())->scopeDeclarationViolations($scan));
    }

    public function testDeclarationViolationsAcceptsAnUntaggedDeclaration(): void
    {
        $declaration = new Declaration('App\\Domain\\Order', 'class', 'App\\Domain', new VisibilityScope([], [], false), 'src/Order.php', 3);

        self::assertSame([], (new ScopeChecker())->declarationViolations($declaration));
    }

    public function testDeclarationViolationsReportsAnUnusableTag(): void
    {
        $scope = new VisibilityScope([], ['parrent'], false);
        $declaration = new Declaration('App\\Domain\\Order', 'class', 'App\\Domain', $scope, 'src/Order.php', 3);

        self::assertSame('invalid_scope', (new ScopeChecker())->declarationViolations($declaration)[0]->rule);
    }

    public function testDeclarationViolationsReportsPublicBesideANarrowingTag(): void
    {
        $scope = new VisibilityScope(['App\\Domain'], ['public', 'namespace'], false);
        $declaration = new Declaration('App\\Domain\\Order', 'class', 'App\\Domain', $scope, 'src/Order.php', 3);

        self::assertCount(1, (new ScopeChecker())->declarationViolations($declaration));
    }

    public function testDeclarationViolationsAcceptsPublicOnItsOwn(): void
    {
        $scope = new VisibilityScope([], ['public'], false);
        $declaration = new Declaration('App\\Domain\\Order', 'class', 'App\\Domain', $scope, 'src/Order.php', 3);

        self::assertSame([], (new ScopeChecker())->declarationViolations($declaration));
    }

    /**
     * @dataProvider providerScopedProject
     */
    #[DataProvider('providerScopedProject')]
    public function testReferenceViolationsReportsOneViolationPerReference(ProjectScan $scan): void
    {
        self::assertCount(1, (new ScopeChecker())->referenceViolations($scan, new ExemptNamespaces()));
    }

    /**
     * @dataProvider providerScopedProject
     */
    #[DataProvider('providerScopedProject')]
    public function testReferenceViolationReportsTheClassScope(ProjectScan $scan): void
    {
        $reference = new Reference('App\\Domain\\Order', null, 'instanceof check', 'App\\Http', 'src/Http.php', 9);

        self::assertSame('App\\Domain\\Order', (new ScopeChecker())->referenceViolation($scan, $reference)?->symbol);
    }

    /**
     * @dataProvider providerScopedProject
     */
    #[DataProvider('providerScopedProject')]
    public function testReferenceViolationPrefersTheMemberScope(ProjectScan $scan): void
    {
        $reference = new Reference('App\\Domain\\Cart', 'wipe', 'static call', 'App\\Http', 'src/Http.php', 9);

        self::assertSame('App\\Domain\\Cart::wipe()', (new ScopeChecker())->referenceViolation($scan, $reference)?->symbol);
    }

    /**
     * @dataProvider providerScopedProject
     */
    #[DataProvider('providerScopedProject')]
    public function testReferenceViolationAcceptsAReferenceInsideTheScope(ProjectScan $scan): void
    {
        $reference = new Reference('App\\Domain\\Order', null, 'instanceof check', 'App\\Domain', 'src/Domain.php', 9);

        self::assertNull((new ScopeChecker())->referenceViolation($scan, $reference));
    }

    /**
     * @dataProvider providerScopedProject
     */
    #[DataProvider('providerScopedProject')]
    public function testReferenceViolationIgnoresAClassOutsideTheAnalyzedSources(ProjectScan $scan): void
    {
        $reference = new Reference('Vendor\\Library\\Client', null, 'instanceof check', 'App\\Http', 'src/Http.php', 9);

        self::assertNull((new ScopeChecker())->referenceViolation($scan, $reference));
    }

    /**
     * @return array<string, array{ProjectScan}>
     */
    public static function providerScopedProject(): array
    {
        $index = new DeclarationIndex();
        $index->addClass('App\\Domain\\Order', [], new Declaration(
            'App\\Domain\\Order',
            'class',
            'App\\Domain',
            new VisibilityScope(['App\\Domain'], ['namespace'], true),
            'src/Domain/Order.php',
            5,
        ));
        $index->addClass('App\\Domain\\Cart', [], new Declaration(
            'App\\Domain\\Cart',
            'class',
            'App\\Domain',
            new VisibilityScope([], [], false),
            'src/Domain/Cart.php',
            5,
        ));
        $index->addClass('App\\Domain\\Mistyped', [], new Declaration(
            'App\\Domain\\Mistyped',
            'class',
            'App\\Domain',
            new VisibilityScope([], ['parrent'], false),
            'src/Domain/Mistyped.php',
            5,
        ));
        $index->addClass('App\\Domain\\AlsoMistyped', [], new Declaration(
            'App\\Domain\\AlsoMistyped',
            'class',
            'App\\Domain',
            new VisibilityScope([], ['parrent'], false),
            'src/Domain/AlsoMistyped.php',
            5,
        ));
        $index->addMember('App\\Domain\\Cart', 'wipe', new Declaration(
            'App\\Domain\\Cart::wipe()',
            'method',
            'App\\Domain',
            new VisibilityScope(['App\\Domain'], ['namespace'], true),
            'src/Domain/Cart.php',
            9,
        ));

        $references = [new Reference('App\\Domain\\Order', '__construct', 'instantiation', 'App\\Http', 'src/Http/Controller.php', 21)];

        return ['one scoped class named from another namespace' => [new ProjectScan($index, $references, 3)]];
    }
}
