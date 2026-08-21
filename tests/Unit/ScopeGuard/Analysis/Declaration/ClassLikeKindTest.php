<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Declaration;

use PhpAiToolkit\ScopeGuard\Analysis\Declaration\ClassLikeKind;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassLikeKind::class)]
final class ClassLikeKindTest extends TestCase
{
    public function testLabelNamesAClass(): void
    {
        self::assertSame('class', (new ClassLikeKind())->label(new \PhpParser\Node\Stmt\Class_('Order')));
    }

    public function testLabelNamesAnInterface(): void
    {
        self::assertSame('interface', (new ClassLikeKind())->label(new \PhpParser\Node\Stmt\Interface_('Contract')));
    }

    public function testLabelNamesATrait(): void
    {
        self::assertSame('trait', (new ClassLikeKind())->label(new \PhpParser\Node\Stmt\Trait_('Shared')));
    }

    public function testLabelNamesAnEnum(): void
    {
        self::assertSame('enum', (new ClassLikeKind())->label(new \PhpParser\Node\Stmt\Enum_('Suit')));
    }

    public function testSupertypesReadsTheParentClass(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Order', ['extends' => new \PhpParser\Node\Name('App\\Base')]);

        self::assertSame(['App\\Base'], (new ClassLikeKind())->supertypes($class));
    }

    public function testSupertypesReadsImplementedInterfaces(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Order', ['implements' => [new \PhpParser\Node\Name('App\\Contract')]]);

        self::assertSame(['App\\Contract'], (new ClassLikeKind())->supertypes($class));
    }

    public function testSupertypesReadsParentInterfaces(): void
    {
        $interface = new \PhpParser\Node\Stmt\Interface_('Contract', ['extends' => [new \PhpParser\Node\Name('App\\Readable')]]);

        self::assertSame(['App\\Readable'], (new ClassLikeKind())->supertypes($interface));
    }

    public function testSupertypesReadsEnumInterfaces(): void
    {
        $enum = new \PhpParser\Node\Stmt\Enum_('Suit', ['implements' => [new \PhpParser\Node\Name('App\\Contract')]]);

        self::assertSame(['App\\Contract'], (new ClassLikeKind())->supertypes($enum));
    }

    public function testSupertypesReadsUsedTraits(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Order', ['stmts' => [new \PhpParser\Node\Stmt\TraitUse([new \PhpParser\Node\Name('App\\Shared')])]]);

        self::assertSame(['App\\Shared'], (new ClassLikeKind())->supertypes($class));
    }

    public function testSupertypesReturnsNothingForAPlainClass(): void
    {
        self::assertSame([], (new ClassLikeKind())->supertypes(new \PhpParser\Node\Stmt\Class_('Order')));
    }
}
