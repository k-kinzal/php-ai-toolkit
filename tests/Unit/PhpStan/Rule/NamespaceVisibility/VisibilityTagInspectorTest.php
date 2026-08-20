<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\NamespaceVisibility;

use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\NamespaceLineage;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityScopeResolver;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityTagInspector;
use PhpAiToolkit\PhpStan\Rule\NamespaceVisibility\VisibilityTagParser;
use PhpAiToolkit\PhpStan\Rule\Shared\ClassLikeKindLabel;
use PhpParser\Node\Stmt\ClassLike;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(VisibilityTagInspector::class)]
#[UsesClass(ClassLikeKindLabel::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(VisibilityErrorBuilder::class)]
#[UsesClass(VisibilityScopeResolver::class)]
#[UsesClass(VisibilityTagParser::class)]
final class VisibilityTagInspectorTest extends TestCase
{
    public function testTagErrorsAcceptsSupportedKeyword(): void
    {
        self::assertSame([], (new VisibilityTagInspector())->tagErrors('/** @visibility namespace */', 'App\\Domain', 'class App\\Domain\\Order', 1));
    }

    public function testTagErrorsAcceptsNamespaceScope(): void
    {
        self::assertSame([], (new VisibilityTagInspector())->tagErrors('/** @visibility App\\Http */', 'App\\Domain', 'class App\\Domain\\Order', 1));
    }

    public function testTagErrorsReportsMistypedKeyword(): void
    {
        $errors = (new VisibilityTagInspector())->tagErrors('/** @visibility parrent */', 'App\\Domain', 'class App\\Domain\\Order', 1);

        self::assertSame(
            'Fix "@visibility parrent" on class App\\Domain\\Order: one bare lowercase word is read as a scope keyword, and "parrent" is not one of "public", "root", "parent", "namespace"; write the keyword you meant, or write "\\parrent" to name the namespace.',
            $errors[0]->getMessage()
        );
    }

    public function testTagErrorsReportsPublicBesideNarrowingTag(): void
    {
        $errors = (new VisibilityTagInspector())->tagErrors("/**\n * @visibility public\n * @visibility namespace\n */", 'App\\Domain', 'class App\\Domain\\Order', 1);

        self::assertSame('customRules.namespaceVisibilityTag', $errors[0]->getIdentifier());
    }

    public function testTagErrorsAcceptsPublicOnItsOwn(): void
    {
        self::assertSame([], (new VisibilityTagInspector())->tagErrors('/** @visibility public */', 'App\\Domain', 'class App\\Domain\\Order', 1));
    }

    public function testTagErrorsReturnsNothingWithoutTag(): void
    {
        self::assertSame([], (new VisibilityTagInspector())->tagErrors(null, 'App\\Domain', 'class App\\Domain\\Order', 1));
    }

    public function testReasonForRejectsParentOfGlobalDeclaration(): void
    {
        self::assertSame(
            'the declaration is in the global namespace, which has no parent namespace to open up',
            (new VisibilityTagInspector())->reasonFor('parent', 'parent', '')
        );
    }

    public function testReasonForRejectsParentOfRootNamespace(): void
    {
        self::assertSame(
            'the parent of namespace "App" is the global namespace, which narrows nothing; write "@visibility namespace" or name an outer namespace',
            (new VisibilityTagInspector())->reasonFor('parent', 'parent', 'App')
        );
    }

    public function testReasonForAcceptsParentOfNestedNamespace(): void
    {
        self::assertNull((new VisibilityTagInspector())->reasonFor('parent', 'parent', 'App\\Domain'));
    }

    public function testReasonForRejectsRootOfGlobalDeclaration(): void
    {
        self::assertSame(
            'the declaration is in the global namespace, which has no root namespace to open up',
            (new VisibilityTagInspector())->reasonFor('root', 'root', '')
        );
    }

    public function testReasonForAcceptsRootOfNamedNamespace(): void
    {
        self::assertNull((new VisibilityTagInspector())->reasonFor('root', 'root', 'App\\Domain'));
    }

    public function testReasonForRejectsNamespaceOfGlobalDeclaration(): void
    {
        self::assertSame(
            'the declaration is in the global namespace, so "namespace" covers every namespace instead of narrowing anything',
            (new VisibilityTagInspector())->reasonFor('namespace', 'namespace', '')
        );
    }

    public function testReasonForRejectsUnusableScope(): void
    {
        self::assertSame(
            'the scope has to be "public", "root", "parent", "namespace", or a namespace name such as "App\\Domain"',
            (new VisibilityTagInspector())->reasonFor('123bad', null, 'App\\Domain')
        );
    }

    public function testReasonForAcceptsEscapedLowercaseNamespace(): void
    {
        self::assertNull((new VisibilityTagInspector())->reasonFor('\\parrent', null, 'App\\Domain'));
    }

    /**
     * @dataProvider providerTaggedClass
     */
    #[DataProvider('providerTaggedClass')]
    public function testErrorsReportsTagOnEveryMemberKind(ClassLike $class): void
    {
        $errors = (new VisibilityTagInspector())->errors($class, 'App\\Domain\\Order');

        self::assertCount(7, $errors);
    }

    /**
     * @dataProvider providerTaggedClass
     */
    #[DataProvider('providerTaggedClass')]
    public function testErrorsNamesTheMemberItReports(ClassLike $class): void
    {
        $errors = (new VisibilityTagInspector())->errors($class, 'App\\Domain\\Order');

        self::assertStringContainsString('method App\\Domain\\Order::place()', $errors[1]->getMessage());
    }

    /**
     * @dataProvider providerTaggedEnum
     */
    #[DataProvider('providerTaggedEnum')]
    public function testErrorsReportsTagOnEnumCase(ClassLike $enum): void
    {
        $errors = (new VisibilityTagInspector())->errors($enum, 'App\\Domain\\Suit');

        self::assertStringContainsString('enum case App\\Domain\\Suit::Hearts', $errors[0]->getMessage());
    }

    /**
     * @dataProvider providerTaggedEnum
     */
    #[DataProvider('providerTaggedEnum')]
    public function testErrorsKeepsEveryReportedEnumCase(ClassLike $enum): void
    {
        self::assertCount(2, (new VisibilityTagInspector())->errors($enum, 'App\\Domain\\Suit'));
    }

    /**
     * @dataProvider providerTaggedClass
     */
    #[DataProvider('providerTaggedClass')]
    public function testMethodErrorsReportsTagOnDeclaredMethods(ClassLike $class): void
    {
        $errors = (new VisibilityTagInspector())->methodErrors($class, 'App\\Domain\\Order', 'App\\Domain');

        self::assertStringContainsString('method App\\Domain\\Order::place()', $errors[0]->getMessage());
    }

    /**
     * @dataProvider providerTaggedClass
     */
    #[DataProvider('providerTaggedClass')]
    public function testMethodErrorsKeepsEveryReportedMethod(ClassLike $class): void
    {
        self::assertCount(2, (new VisibilityTagInspector())->methodErrors($class, 'App\\Domain\\Order', 'App\\Domain'));
    }

    /**
     * @dataProvider providerTaggedClass
     */
    #[DataProvider('providerTaggedClass')]
    public function testPropertyErrorsReportsTagOnDeclaredProperties(ClassLike $class): void
    {
        $errors = (new VisibilityTagInspector())->propertyErrors($class, 'App\\Domain\\Order', 'App\\Domain');

        self::assertStringContainsString('property App\\Domain\\Order::$total', $errors[0]->getMessage());
    }

    /**
     * @dataProvider providerTaggedClass
     */
    #[DataProvider('providerTaggedClass')]
    public function testPropertyErrorsKeepsEveryReportedProperty(ClassLike $class): void
    {
        self::assertCount(2, (new VisibilityTagInspector())->propertyErrors($class, 'App\\Domain\\Order', 'App\\Domain'));
    }

    /**
     * @dataProvider providerTaggedClass
     */
    #[DataProvider('providerTaggedClass')]
    public function testConstantErrorsReportsTagOnDeclaredConstants(ClassLike $class): void
    {
        $errors = (new VisibilityTagInspector())->constantErrors($class, 'App\\Domain\\Order', 'App\\Domain');

        self::assertStringContainsString('constant App\\Domain\\Order::STATUS', $errors[0]->getMessage());
    }

    /**
     * @dataProvider providerTaggedClass
     */
    #[DataProvider('providerTaggedClass')]
    public function testConstantErrorsKeepsEveryReportedConstant(ClassLike $class): void
    {
        self::assertCount(2, (new VisibilityTagInspector())->constantErrors($class, 'App\\Domain\\Order', 'App\\Domain'));
    }

    public function testDocCommentOfReturnsNullWithoutComment(): void
    {
        self::assertNull((new VisibilityTagInspector())->docCommentOf(new \PhpParser\Node\Stmt\Class_('Order')));
    }

    /**
     * @return array<string, array{ClassLike}>
     *
     * @throws RuntimeException when the installed parser produces no class
     */
    public static function providerTaggedClass(): array
    {
        $source = <<<'SOURCE'
            <?php
            /** @visibility parrent */
            class Order {
                /** @visibility parrent */
                public const STATUS = 1;
                /** @visibility parrent */
                public const ORIGIN = 2;
                /** @visibility parrent */
                public int $total = 0;
                /** @visibility parrent */
                public int $discount = 0;
                /** @visibility parrent */
                public function place(): void {}
                /** @visibility parrent */
                public function cancel(): void {}
            }
            SOURCE;

        $statements = (new PhpParserBridge())->parser()->parse($source);
        $classLike = $statements[0] ?? null;
        if (!$classLike instanceof ClassLike) {
            throw new RuntimeException('The installed parser produced no class from the snippet.');
        }

        return ['class with a mistyped tag on every member' => [$classLike]];
    }

    /**
     * @return array<string, array{ClassLike}>
     *
     * @throws RuntimeException when the installed parser produces no enum
     */
    public static function providerTaggedEnum(): array
    {
        $source = <<<'SOURCE'
            <?php
            enum Suit {
                /** @visibility parrent */
                case Hearts;
                /** @visibility parrent */
                case Spades;
            }
            SOURCE;

        $statements = (new PhpParserBridge())->parser()->parse($source);
        $classLike = $statements[0] ?? null;
        if (!$classLike instanceof ClassLike) {
            throw new RuntimeException('The installed parser produced no enum from the snippet.');
        }

        return ['enum with a mistyped tag on a case' => [$classLike]];
    }
}
