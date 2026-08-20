<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\NamespaceVisibility;

use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\ReferencedClassResolver;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\TypeReferenceInspector;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityAccessChecker;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(TypeReferenceInspector::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(ReferencedClassResolver::class)]
#[UsesClass(VisibilityAccessChecker::class)]
final class TypeReferenceInspectorTest extends TestCase
{
    /**
     * @dataProvider providerClassWithSupertypes
     */
    #[DataProvider('providerClassWithSupertypes')]
    public function testSupertypeNamesCollectsTheParentClass(ClassLike $class): void
    {
        self::assertSame('Base', (new TypeReferenceInspector())->supertypeNames($class)[0]->toString());
    }

    /**
     * @dataProvider providerClassWithSupertypes
     */
    #[DataProvider('providerClassWithSupertypes')]
    public function testSupertypeNamesCollectsEveryInterface(ClassLike $class): void
    {
        self::assertSame('Extra', (new TypeReferenceInspector())->supertypeNames($class)[2]->toString());
    }

    /**
     * @dataProvider providerClassWithSupertypes
     */
    #[DataProvider('providerClassWithSupertypes')]
    public function testSupertypeNamesCollectsUsedTraits(ClassLike $class): void
    {
        self::assertSame('Shared', (new TypeReferenceInspector())->supertypeNames($class)[3]->toString());
    }

    /**
     * @dataProvider providerInterfaceWithParents
     */
    #[DataProvider('providerInterfaceWithParents')]
    public function testSupertypeNamesCollectsInterfaceParents(ClassLike $interface): void
    {
        self::assertCount(2, (new TypeReferenceInspector())->supertypeNames($interface));
    }

    /**
     * @dataProvider providerEnumWithInterface
     */
    #[DataProvider('providerEnumWithInterface')]
    public function testSupertypeNamesCollectsEnumInterfaces(ClassLike $enum): void
    {
        self::assertCount(1, (new TypeReferenceInspector())->supertypeNames($enum));
    }

    /**
     * @dataProvider providerClassWithMemberTypes
     */
    #[DataProvider('providerClassWithMemberTypes')]
    public function testMemberTypeNamesCollectsParameterTypes(ClassLike $class): void
    {
        self::assertSame('Cart', (new TypeReferenceInspector())->memberTypeNames($class)[0]->toString());
    }

    /**
     * @dataProvider providerClassWithMemberTypes
     */
    #[DataProvider('providerClassWithMemberTypes')]
    public function testMemberTypeNamesCollectsReturnTypes(ClassLike $class): void
    {
        self::assertSame('Receipt', (new TypeReferenceInspector())->memberTypeNames($class)[1]->toString());
    }

    /**
     * @dataProvider providerClassWithMemberTypes
     */
    #[DataProvider('providerClassWithMemberTypes')]
    public function testMemberTypeNamesCollectsPropertyTypes(ClassLike $class): void
    {
        self::assertSame('Order', (new TypeReferenceInspector())->memberTypeNames($class)[2]->toString());
    }

    /**
     * @dataProvider providerClassWithSupertypes
     */
    #[DataProvider('providerClassWithSupertypes')]
    public function testErrorsReturnsNothingForUnresolvedNames(ClassLike $class): void
    {
        self::assertSame([], (new TypeReferenceInspector())->errors($class, self::createStub(Scope::class), 'Other\\Place'));
    }

    /**
     * @return array<string, array{ClassLike}>
     *
     * @throws RuntimeException when the installed parser produces no class
     */
    public static function providerClassWithSupertypes(): array
    {
        $statements = (new PhpParserBridge())->parser()->parse('<?php class Order extends Base implements Contract, Extra { use Shared; }');
        $class = $statements[0] ?? null;
        if (!$class instanceof ClassLike) {
            throw new RuntimeException('The installed parser produced no class from the snippet.');
        }

        return ['class with a parent, interfaces, and a trait' => [$class]];
    }

    /**
     * @return array<string, array{ClassLike}>
     *
     * @throws RuntimeException when the installed parser produces no interface
     */
    public static function providerInterfaceWithParents(): array
    {
        $statements = (new PhpParserBridge())->parser()->parse('<?php interface Contract extends Readable, Writable {}');
        $interface = $statements[0] ?? null;
        if (!$interface instanceof ClassLike) {
            throw new RuntimeException('The installed parser produced no interface from the snippet.');
        }

        return ['interface with two parents' => [$interface]];
    }

    /**
     * @return array<string, array{ClassLike}>
     *
     * @throws RuntimeException when the installed parser produces no enum
     */
    public static function providerEnumWithInterface(): array
    {
        $statements = (new PhpParserBridge())->parser()->parse('<?php enum Suit implements Contract { case Hearts; }');
        $enum = $statements[0] ?? null;
        if (!$enum instanceof ClassLike) {
            throw new RuntimeException('The installed parser produced no enum from the snippet.');
        }

        return ['enum with an interface' => [$enum]];
    }

    /**
     * @return array<string, array{ClassLike}>
     *
     * @throws RuntimeException when the installed parser produces no class
     */
    public static function providerClassWithMemberTypes(): array
    {
        $statements = (new PhpParserBridge())->parser()->parse('<?php class Checkout { public ?Order $order = null; public function pay(Cart $cart, int $amount): ?Receipt { return null; } }');
        $class = $statements[0] ?? null;
        if (!$class instanceof ClassLike) {
            throw new RuntimeException('The installed parser produced no class from the snippet.');
        }

        return ['class with typed members' => [$class]];
    }
}
