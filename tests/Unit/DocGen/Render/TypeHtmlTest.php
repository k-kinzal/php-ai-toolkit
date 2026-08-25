<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeForParameterNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ObjectShapeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\DocTag;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\TypeHtml;
use Toolkit\DocGen\Render\TypeRenderContext;

/**
 * @covers \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Analysis\Model\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Analysis\Model\DocTag
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Render\TypeRenderContext
 */
#[CoversClass(TypeHtml::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocTag::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TypeRenderContext::class)]
final class TypeHtmlTest extends TestCase
{
    public function testRenderPrefersAnnotatedOverNative(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param list<int> $x */');
        self::assertNotNull($doc);

        self::assertSame(
            '<span class="t-key">list</span>&lt;<span class="t-key">int</span>&gt;',
            (new TypeHtml())->render($doc->params['$x']->type, 'array', $context),
        );
    }

    public function testRenderFallsBackToNativeString(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        self::assertSame('<span class="t-key">string</span>', (new TypeHtml())->render(null, 'string', $context));
    }

    public function testRenderReturnsEmptyStringWithoutAnyType(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        self::assertSame('', (new TypeHtml())->render(null, null, $context));
    }

    public function testNodeRendersNullableDocumentedClass(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 3, 20, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param ?Widget $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertNotNull($type);

        self::assertSame(
            '?<a class="t-name k-class" href="../../../demo/pkg/Demo/class.Widget.html" title="Demo\Widget">Widget</a>',
            (new TypeHtml())->node($type, $context),
        );
    }

    public function testNodeRendersUnionWithParenthesizedNesting(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 3, 20, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param int|(Widget&Traversable) $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertNotNull($type);

        self::assertSame(
            '<span class="t-key">int</span>|(<a class="t-name k-class" href="../../../demo/pkg/Demo/class.Widget.html" title="Demo\Widget">Widget</a>&amp;<span class="t-ext" title="Demo\Traversable">Traversable</span>)',
            (new TypeHtml())->node($type, $context),
        );
    }

    public function testNodeRendersArrayTypeSuffix(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 3, 20, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param Widget[] $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertNotNull($type);

        self::assertSame(
            '<a class="t-name k-class" href="../../../demo/pkg/Demo/class.Widget.html" title="Demo\Widget">Widget</a>[]',
            (new TypeHtml())->node($type, $context),
        );
    }

    public function testNodeRendersThisType(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @return $this */');
        self::assertNotNull($doc);
        $tag = $doc->return;
        self::assertNotNull($tag);
        $type = $tag->type;
        self::assertNotNull($type);

        self::assertSame('<span class="t-key">$this</span>', (new TypeHtml())->node($type, $context));
    }

    public function testNodeRendersConstTypeLiteral(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param Widget::FOO $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertNotNull($type);

        self::assertSame('<span class="t-lit">Widget::FOO</span>', (new TypeHtml())->node($type, $context));
    }

    public function testMiscNodeRendersOffsetAccessType(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param Config[\'key\'] $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertNotNull($type);

        self::assertSame(
            '<span class="t-ext" title="Demo\Config">Config</span>[<span class="t-lit">&#039;key&#039;</span>]',
            (new TypeHtml())->miscNode($type, $context),
        );
    }

    /**
     * @dataProvider providerConstantExpression
     */
    #[DataProvider('providerConstantExpression')]
    public function testConstExprPrintsAConstantAsPhpWritesIt(ConstExprNode $expr, string $expected): void
    {
        self::assertSame($expected, (new TypeHtml())->constExpr($expr));
    }

    /**
     * @return array<string, array{ConstExprNode, string}>
     *
     * @throws RuntimeException when the installed parser reads no constant type
     */
    public static function providerConstantExpression(): array
    {
        $cases = [];
        foreach (['string' => ["/** @param 'key' \$x */", "'key'"], 'integer' => ['/** @param 42 $x */', '42']] as $label => $case) {
            $doc = (new DocBlockReader())->read($case[0]);
            $type = $doc === null ? null : ($doc->params['$x']->type ?? null);
            if (!$type instanceof ConstTypeNode) {
                throw new RuntimeException('The installed parser read no constant type from the snippet.');
            }

            $cases[$label] = [$type->constExpr, $case[1]];
        }

        return $cases;
    }

    public function testMiscNodeRendersConstTypeLiteral(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param Widget::FOO $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertNotNull($type);

        self::assertSame('<span class="t-lit">Widget::FOO</span>', (new TypeHtml())->miscNode($type, $context));
    }

    public function testMiscNodeEscapesUnhandledNodeText(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param Foo<int> $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertNotNull($type);

        self::assertSame('Foo&lt;int&gt;', (new TypeHtml())->miscNode($type, $context));
    }

    public function testIdentifierStylesKeyword(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param int $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertInstanceOf(IdentifierTypeNode::class, $type);

        self::assertSame('<span class="t-key">int</span>', (new TypeHtml())->identifier($type, $context));
    }

    public function testIdentifierStylesTemplateParameter(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], ['T'], [], $table);
        $doc = (new DocBlockReader())->read('/** @param T $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertInstanceOf(IdentifierTypeNode::class, $type);

        self::assertSame('<span class="t-gen">T</span>', (new TypeHtml())->identifier($type, $context));
    }

    public function testIdentifierLinksAliasAnchor(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], ['Shape' => '#alias.Shape'], $table);
        $doc = (new DocBlockReader())->read('/** @param Shape $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertInstanceOf(IdentifierTypeNode::class, $type);

        self::assertSame('<a class="t-alias" href="#alias.Shape">Shape</a>', (new TypeHtml())->identifier($type, $context));
    }

    public function testIdentifierLinksDocumentedClass(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 3, 20, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param Widget $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertInstanceOf(IdentifierTypeNode::class, $type);

        self::assertSame(
            '<a class="t-name k-class" href="../../../demo/pkg/Demo/class.Widget.html" title="Demo\Widget">Widget</a>',
            (new TypeHtml())->identifier($type, $context),
        );
    }

    public function testIdentifierMarksUnknownClassExternal(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param Unknown $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertInstanceOf(IdentifierTypeNode::class, $type);

        self::assertSame('<span class="t-ext" title="Demo\Unknown">Unknown</span>', (new TypeHtml())->identifier($type, $context));
    }

    public function testClassNameLinksLeadingBackslashName(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 3, 20, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        self::assertSame(
            '<a class="t-name k-class" href="../../../demo/pkg/Demo/class.Widget.html" title="Demo\Widget">Widget</a>',
            (new TypeHtml())->className('\Demo\Widget', $context),
        );
    }

    public function testClassNameMarksUnknownClassExternal(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        self::assertSame(
            '<span class="t-ext" title="Demo\Acme\Thing">Thing</span>',
            (new TypeHtml())->className('Acme\Thing', $context),
        );
    }

    public function testResolveStripsLeadingBackslash(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        self::assertSame('Acme\Thing', (new TypeHtml())->resolve('\Acme\Thing', $context));
    }

    public function testResolveUsesUseMapAlias(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', ['w' => 'Demo\Widget'], [], [], $table);

        self::assertSame('Demo\Widget', (new TypeHtml())->resolve('W', $context));
    }

    public function testResolveExpandsUseMapSubNamespace(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', ['alias' => 'Vendor\Pkg'], [], [], $table);

        self::assertSame('Vendor\Pkg\Sub', (new TypeHtml())->resolve('Alias\Sub', $context));
    }

    public function testResolvePrefersNamespaceRelativeSymbol(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 3, 20, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        self::assertSame('Demo\Widget', (new TypeHtml())->resolve('Widget', $context));
    }

    public function testResolveFallsBackToGlobalSymbol(): void
    {
        $global = new ClassLikeDoc('GlobalThing', 'GlobalThing', '', 'class', 'demo/pkg', 'src/G.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($global);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        self::assertSame('GlobalThing', (new TypeHtml())->resolve('GlobalThing', $context));
    }

    public function testResolveDefaultsToNamespacePrefixForUnknownName(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        self::assertSame('Demo\Unknown', (new TypeHtml())->resolve('Unknown', $context));
    }

    public function testGenericRendersKeywordContainerWithLinkedArgument(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 3, 20, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param list<Widget> $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertInstanceOf(GenericTypeNode::class, $type);

        self::assertSame(
            '<span class="t-key">list</span>&lt;<a class="t-name k-class" href="../../../demo/pkg/Demo/class.Widget.html" title="Demo\Widget">Widget</a>&gt;',
            (new TypeHtml())->generic($type, $context),
        );
    }

    public function testArrayShapeRendersKeysAndOptionalMarker(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param array{a: int, b?: string} $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertInstanceOf(ArrayShapeNode::class, $type);

        self::assertSame(
            '<span class="t-key">array</span>{<span class="t-shape-key">a</span>: <span class="t-key">int</span>, <span class="t-shape-key">b</span>?: <span class="t-key">string</span>}',
            (new TypeHtml())->arrayShape($type, $context),
        );
    }

    public function testArrayShapeAppendsEllipsisWhenUnsealed(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param array{a: int, ...} $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertInstanceOf(ArrayShapeNode::class, $type);

        self::assertSame(
            '<span class="t-key">array</span>{<span class="t-shape-key">a</span>: <span class="t-key">int</span>, ...}',
            (new TypeHtml())->arrayShape($type, $context),
        );
    }

    public function testObjectShapeRendersKeyedProperties(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param object{a: int, b?: string} $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertInstanceOf(ObjectShapeNode::class, $type);

        self::assertSame(
            '<span class="t-key">object</span>{<span class="t-shape-key">a</span>: <span class="t-key">int</span>, <span class="t-shape-key">b</span>?: <span class="t-key">string</span>}',
            (new TypeHtml())->objectShape($type, $context),
        );
    }

    public function testCallableTypeRendersParametersAndReturn(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @param callable(int $a, string ...$b): void $x */');
        self::assertNotNull($doc);
        $type = $doc->params['$x']->type;
        self::assertInstanceOf(CallableTypeNode::class, $type);

        self::assertSame(
            '<span class="t-key">callable</span>(<span class="t-key">int</span> $a, <span class="t-key">string</span>... $b): <span class="t-key">void</span>',
            (new TypeHtml())->callableType($type, $context),
        );
    }

    public function testConditionalRendersParameterCondition(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 3, 20, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $doc = (new DocBlockReader())->read('/** @return ($x is null ? int : Widget) */');
        self::assertNotNull($doc);
        $tag = $doc->return;
        self::assertNotNull($tag);
        $type = $tag->type;
        self::assertInstanceOf(ConditionalTypeForParameterNode::class, $type);

        self::assertSame(
            '(<span class="t-var">$x</span> <span class="t-key">is</span> <span class="t-key">null</span> ? <span class="t-key">int</span> : <a class="t-name k-class" href="../../../demo/pkg/Demo/class.Widget.html" title="Demo\Widget">Widget</a>)',
            (new TypeHtml())->conditional($type, $context),
        );
    }

    public function testNativeStringLinksNullableUnion(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 3, 20, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        self::assertSame(
            '?<a class="t-name k-class" href="../../../demo/pkg/Demo/class.Widget.html" title="Demo\Widget">Widget</a>|<span class="t-key">string</span>',
            (new TypeHtml())->nativeString('?Demo\Widget|string', $context),
        );
    }

    public function testNativeStringRendersIntersectionParts(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 3, 20, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        self::assertSame(
            '<a class="t-name k-class" href="../../../demo/pkg/Demo/class.Widget.html" title="Demo\Widget">Widget</a>&amp;<span class="t-ext" title="Demo\Countable">Countable</span>',
            (new TypeHtml())->nativeString('Widget&Countable', $context),
        );
    }
}
