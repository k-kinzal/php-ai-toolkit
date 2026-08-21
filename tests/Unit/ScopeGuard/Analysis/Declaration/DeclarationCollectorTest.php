<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Declaration;

use PhpAiToolkit\ScopeGuard\Analysis\Declaration\ClassLikeKind;
use PhpAiToolkit\ScopeGuard\Analysis\Declaration\Declaration;
use PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationCollector;
use PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex;
use PhpAiToolkit\ScopeGuard\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\NamespaceLineage;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScope;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityScopeResolver;
use PhpAiToolkit\ScopeGuard\Analysis\Scope\VisibilityTagParser;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;
use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\ClassLike;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeclarationCollector::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(Declaration::class)]
#[UsesClass(DeclarationIndex::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(VisibilityScope::class)]
#[UsesClass(VisibilityScopeResolver::class)]
#[UsesClass(VisibilityTagParser::class)]
final class DeclarationCollectorTest extends TestCase
{
    /**
     * @dataProvider providerScopedClass
     */
    #[DataProvider('providerScopedClass')]
    public function testCollectRecordsTheClassScope(ClassLike $class): void
    {
        $index = new DeclarationIndex();
        (new DeclarationCollector())->collect([$class], 'src/Order.php', $index);

        self::assertSame(['App\\Domain'], $index->classDeclaration('App\\Domain\\Order')?->scope->allowedNamespaces);
    }

    /**
     * @dataProvider providerScopedClass
     */
    #[DataProvider('providerScopedClass')]
    public function testCollectRecordsTheDeclarationKind(ClassLike $class): void
    {
        $index = new DeclarationIndex();
        (new DeclarationCollector())->collect([$class], 'src/Order.php', $index);

        self::assertSame('class', $index->classDeclaration('App\\Domain\\Order')?->kind);
    }

    /**
     * @dataProvider providerScopedClass
     */
    #[DataProvider('providerScopedClass')]
    public function testCollectRecordsEveryMemberKind(ClassLike $class): void
    {
        $index = new DeclarationIndex();
        (new DeclarationCollector())->collect([$class], 'src/Order.php', $index);

        self::assertCount(4, $index->declarations());
    }

    /**
     * @dataProvider providerScopedClass
     */
    #[DataProvider('providerScopedClass')]
    public function testCollectMembersNamesAMethodWithParentheses(ClassLike $class): void
    {
        $index = new DeclarationIndex();
        (new DeclarationCollector())->collectMembers($class, 'App\\Domain\\Order', 'App\\Domain', 'src/Order.php', $index);

        self::assertSame('App\\Domain\\Order::place()', $index->memberDeclaration('App\\Domain\\Order', 'place')?->symbol);
    }

    /**
     * @dataProvider providerScopedClass
     */
    #[DataProvider('providerScopedClass')]
    public function testCollectMembersNamesAPropertyWithADollarSign(ClassLike $class): void
    {
        $index = new DeclarationIndex();
        (new DeclarationCollector())->collectMembers($class, 'App\\Domain\\Order', 'App\\Domain', 'src/Order.php', $index);

        self::assertSame('App\\Domain\\Order::$total', $index->memberDeclaration('App\\Domain\\Order', 'total')?->symbol);
    }

    /**
     * @dataProvider providerScopedClass
     */
    #[DataProvider('providerScopedClass')]
    public function testCollectMembersRecordsConstants(ClassLike $class): void
    {
        $index = new DeclarationIndex();
        (new DeclarationCollector())->collectMembers($class, 'App\\Domain\\Order', 'App\\Domain', 'src/Order.php', $index);

        self::assertSame('constant', $index->memberDeclaration('App\\Domain\\Order', 'STATUS')?->kind);
    }

    /**
     * @dataProvider providerScopedEnum
     */
    #[DataProvider('providerScopedEnum')]
    public function testCollectMembersRecordsEnumCases(ClassLike $enum): void
    {
        $index = new DeclarationIndex();
        (new DeclarationCollector())->collectMembers($enum, 'App\\Domain\\Suit', 'App\\Domain', 'src/Suit.php', $index);

        self::assertSame('enum case', $index->memberDeclaration('App\\Domain\\Suit', 'Hearts')?->kind);
    }

    public function testCollectSkipsAnonymousClasses(): void
    {
        $index = new DeclarationIndex();
        (new DeclarationCollector())->collect([new \PhpParser\Node\Stmt\Class_(null)], 'src/Order.php', $index);

        self::assertSame([], $index->declarations());
    }

    public function testCollectSkipsNodesThatDeclareNothing(): void
    {
        $index = new DeclarationIndex();
        (new DeclarationCollector())->collect([new \PhpParser\Node\Stmt\Nop()], 'src/Order.php', $index);

        self::assertSame([], $index->declarations());
    }

    public function testAddMemberRecordsTheGivenSymbol(): void
    {
        $index = new DeclarationIndex();
        (new DeclarationCollector())->addMember($index, 'App\\Domain\\Order', 'place', 'App\\Domain\\Order::place()', 'method', 'App\\Domain', 'src/Order.php', new \PhpParser\Node\Stmt\Nop());

        self::assertSame('method', $index->memberDeclaration('App\\Domain\\Order', 'place')?->kind);
    }

    public function testDocCommentReadsTheRawText(): void
    {
        $node = new \PhpParser\Node\Stmt\Nop();
        $node->setDocComment(new Doc('/** @visibility namespace */'));

        self::assertSame('/** @visibility namespace */', (new DeclarationCollector())->docComment($node));
    }

    public function testDocCommentReturnsNullWithoutComment(): void
    {
        self::assertNull((new DeclarationCollector())->docComment(new \PhpParser\Node\Stmt\Nop()));
    }

    /**
     * @return array<string, array{ClassLike}>
     *
     * @throws ScopeGuardException when the installed parser produces no class
     */
    public static function providerScopedClass(): array
    {
        $source = '<?php /** @visibility namespace */ class Order { const STATUS = 1; public $total = 0; public function place() {} }';
        $statements = (new PhpParserBridge())->parser()->parse($source);
        $class = $statements[0] ?? null;
        if (!$class instanceof ClassLike) {
            throw new ScopeGuardException('The installed parser produced no class from the snippet.');
        }

        $class->namespacedName = new \PhpParser\Node\Name('App\\Domain\\Order');

        return ['a scoped class with one member of each kind' => [$class]];
    }

    /**
     * @return array<string, array{ClassLike}>
     *
     * @throws ScopeGuardException when the installed parser produces no enum
     */
    public static function providerScopedEnum(): array
    {
        $statements = (new PhpParserBridge())->parser()->parse('<?php enum Suit { case Hearts; }');
        $enum = $statements[0] ?? null;
        if (!$enum instanceof ClassLike) {
            throw new ScopeGuardException('The installed parser produced no enum from the snippet.');
        }

        $enum->namespacedName = new \PhpParser\Node\Name('App\\Domain\\Suit');

        return ['an enum with one case' => [$enum]];
    }
}
