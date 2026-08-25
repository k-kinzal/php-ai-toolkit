<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Declaration;

use PhpAiToolkit\ScopeGuard\Analysis\Declaration\Declaration;
use PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Declaration\Declaration
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage
 * @uses \PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScope
 */
#[CoversClass(DeclarationIndex::class)]
#[UsesClass(Declaration::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(VisibilityScope::class)]
final class DeclarationIndexTest extends TestCase
{
    public function testAddClassMakesTheDeclarationFindable(): void
    {
        $index = new DeclarationIndex();
        $index->addClass('App\\Domain\\Order', [], new Declaration('App\\Domain\\Order', 'class', 'App\\Domain', new VisibilityScope([], [], false), 'src/Order.php', 3));

        self::assertSame('App\\Domain\\Order', $index->classDeclaration('App\\Domain\\Order')?->symbol);
    }

    public function testClassDeclarationIgnoresNameCase(): void
    {
        $index = new DeclarationIndex();
        $index->addClass('App\\Domain\\Order', [], new Declaration('App\\Domain\\Order', 'class', 'App\\Domain', new VisibilityScope([], [], false), 'src/Order.php', 3));

        self::assertNotNull($index->classDeclaration('app\\domain\\order'));
    }

    public function testClassDeclarationReturnsNullForAnUnknownClass(): void
    {
        self::assertNull((new DeclarationIndex())->classDeclaration('App\\Domain\\Order'));
    }

    public function testAddMemberMakesTheMemberFindable(): void
    {
        $index = new DeclarationIndex();
        $index->addMember('App\\Domain\\Order', 'place', new Declaration('App\\Domain\\Order::place()', 'method', 'App\\Domain', new VisibilityScope([], [], false), 'src/Order.php', 9));

        self::assertSame('App\\Domain\\Order::place()', $index->memberDeclaration('App\\Domain\\Order', 'place')?->symbol);
    }

    public function testMemberDeclarationFollowsDeclaredParents(): void
    {
        $index = new DeclarationIndex();
        $index->addClass('App\\Domain\\Order', ['App\\Domain\\Base'], new Declaration('App\\Domain\\Order', 'class', 'App\\Domain', new VisibilityScope([], [], false), 'src/Order.php', 3));
        $index->addMember('App\\Domain\\Base', 'place', new Declaration('App\\Domain\\Base::place()', 'method', 'App\\Domain', new VisibilityScope([], [], false), 'src/Base.php', 9));

        self::assertSame('App\\Domain\\Base::place()', $index->memberDeclaration('App\\Domain\\Order', 'place')?->symbol);
    }

    public function testMemberDeclarationSurvivesAParentCycle(): void
    {
        $index = new DeclarationIndex();
        $index->addClass('App\\One', ['App\\Two'], new Declaration('App\\One', 'class', 'App', new VisibilityScope([], [], false), 'src/One.php', 3));
        $index->addClass('App\\Two', ['App\\One'], new Declaration('App\\Two', 'class', 'App', new VisibilityScope([], [], false), 'src/Two.php', 3));

        self::assertNull($index->memberDeclaration('App\\One', 'place'));
    }

    public function testMemberDeclarationReturnsNullForAnUnknownMember(): void
    {
        self::assertNull((new DeclarationIndex())->memberDeclaration('App\\Domain\\Order', 'place'));
    }

    public function testDeclarationsReturnsEveryRecordedDeclaration(): void
    {
        $index = new DeclarationIndex();
        $index->addClass('App\\Domain\\Order', [], new Declaration('App\\Domain\\Order', 'class', 'App\\Domain', new VisibilityScope([], [], false), 'src/Order.php', 3));
        $index->addMember('App\\Domain\\Order', 'place', new Declaration('App\\Domain\\Order::place()', 'method', 'App\\Domain', new VisibilityScope([], [], false), 'src/Order.php', 9));

        self::assertCount(2, $index->declarations());
    }

    public function testMemberKeyLowercasesOnlyTheClassName(): void
    {
        self::assertSame('app\\domain\\order::Place', (new DeclarationIndex())->memberKey('App\\Domain\\Order', 'Place'));
    }
}
