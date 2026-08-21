<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\LineDiffer;
use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind;
use PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc;
use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PhpAiToolkit\DocGen\Analysis\Model\DocTag;
use PhpAiToolkit\DocGen\Analysis\Model\EnumCaseDoc;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\MethodDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc;
use PhpAiToolkit\DocGen\Analysis\Model\PropertyDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TemplateDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\Parse\AstParser;
use PhpAiToolkit\DocGen\Analysis\Parse\ClassLikeBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ConstantBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\EnumCaseBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\FileSymbolCollector;
use PhpAiToolkit\DocGen\Analysis\Parse\FileSymbols;
use PhpAiToolkit\DocGen\Analysis\Parse\FunctionBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\MethodBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\ParameterBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ParameterModifiers;
use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\DocGen\Analysis\Parse\PropertyBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\SymbolContext;
use PhpAiToolkit\DocGen\Analysis\Parse\UseMapCollector;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Parallel\WorkerCount;
use PhpAiToolkit\DocGen\Parallel\WorkerPool;
use PhpAiToolkit\DocGen\Parallel\WorkScheduler;
use PhpAiToolkit\DocGen\Render\AssetPublisher;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PhpAiToolkit\DocGen\Render\Diff\MarkdownDiffHtml;
use PhpAiToolkit\DocGen\Render\Diff\SourceDiffHtml;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\Page\AllItemsPage;
use PhpAiToolkit\DocGen\Render\Page\BreadcrumbHtml;
use PhpAiToolkit\DocGen\Render\Page\ClassLikePage;
use PhpAiToolkit\DocGen\Render\Page\DocTextHtml;
use PhpAiToolkit\DocGen\Render\Page\DocumentPage;
use PhpAiToolkit\DocGen\Render\Page\ExampleHtml;
use PhpAiToolkit\DocGen\Render\Page\FunctionPage;
use PhpAiToolkit\DocGen\Render\Page\GraphSvg;
use PhpAiToolkit\DocGen\Render\Page\IndexPage;
use PhpAiToolkit\DocGen\Render\Page\LayerPage;
use PhpAiToolkit\DocGen\Render\Page\MemberHtml;
use PhpAiToolkit\DocGen\Render\Page\NamespacePage;
use PhpAiToolkit\DocGen\Render\Page\PackagePage;
use PhpAiToolkit\DocGen\Render\Page\PrivateSurfaceHtml;
use PhpAiToolkit\DocGen\Render\Page\RelationsHtml;
use PhpAiToolkit\DocGen\Render\Page\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\SignatureHtml;
use PhpAiToolkit\DocGen\Render\Page\SourcePage;
use PhpAiToolkit\DocGen\Render\Page\SymbolListHtml;
use PhpAiToolkit\DocGen\Render\Page\UsageListHtml;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\SearchIndexBuilder;
use PhpAiToolkit\DocGen\Render\Signature\PageSignature;
use PhpAiToolkit\DocGen\Render\Signature\SidebarDigest;
use PhpAiToolkit\DocGen\Render\SiteRenderer;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\SocialCard;
use PhpAiToolkit\DocGen\Render\SocialMeta;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PhpAiToolkit\DocGen\Render\TypeRenderContext;
use PhpAiToolkit\Doctest\Analysis\AssertionScanner;
use PhpAiToolkit\Doctest\Analysis\DoctestExtractor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SignatureHtml::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocTag::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentPage::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(EnumCaseDoc::class)]
#[UsesClass(ExampleHtml::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(FunctionPage::class)]
#[UsesClass(GraphSvg::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(IndexPage::class)]
#[UsesClass(LayerPage::class)]
#[UsesClass(LineDiffer::class)]
#[UsesClass(MarkdownDiffHtml::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(MemberHtml::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NamespacePage::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PackagePage::class)]
#[UsesClass(PageChrome::class)]
#[UsesClass(PageSignature::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PrivateSurfaceHtml::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyDoc::class)]
#[UsesClass(RelationsHtml::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarDigest::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SourceDiffHtml::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TemplateDoc::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeRenderContext::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UsageListHtml::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
final class SignatureHtmlTest extends TestCase
{
    public function testClassSignatureRendersKeywordsAndLinkedInterface(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

interface Renderer
{
}

final class Widget implements Renderer
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[1];
        $table = new SymbolTable();
        $table->registerClassLike($symbols->classLikes[0]);
        $table->registerClassLike($symbols->classLikes[1]);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        $html = (new SignatureHtml())->classSignature($services, $widget, $context);

        self::assertSame(
            '<pre class="signature"><code><span class="t-key">final</span> <span class="t-key">class</span> <span class="sig-name">Widget</span>' . "\n"
            . '    <span class="t-key">implements</span> <a class="t-name k-interface" href="../../../demo/pkg/Demo/interface.Renderer.html" title="Demo\Renderer">Renderer</a></code></pre>' . "\n",
            $html,
        );
    }

    public function testClassSignatureRendersTemplatesAndGenericImplements(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * @template T of object
 * @implements \ArrayAccess<int, T>
 */
abstract class Bag implements \ArrayAccess
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Bag.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Bag.php', false);
        $bag = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($bag);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Bag.html', 'Demo', [], ['T'], [], $table);

        $html = (new SignatureHtml())->classSignature($services, $bag, $context);

        self::assertStringContainsString('<span class="t-key">abstract</span> <span class="t-key">class</span> <span class="sig-name">Bag</span>&lt;<span class="t-gen">T</span> <span class="t-key">of</span> <span class="t-key">object</span>&gt;', $html);
        self::assertStringContainsString('<span class="t-key">implements</span> <span class="t-ext" title="ArrayAccess">ArrayAccess</span>&lt;<span class="t-key">int</span>, <span class="t-gen">T</span>&gt;', $html);
        self::assertSame(1, substr_count($html, 'title="ArrayAccess"'));
    }

    public function testParentClauseDeduplicatesTagCoveredNames(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * @implements \ArrayAccess<int, string>
 */
abstract class Bag implements \ArrayAccess
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Bag.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Bag.php', false);
        $bag = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($bag);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Bag.html', 'Demo', [], [], [], $table);
        $tags = $bag->docBlock !== null ? $bag->docBlock->implementsTags : [];

        $html = (new SignatureHtml())->parentClause($services, 'implements', $bag->implements, $tags, $context);

        self::assertStringStartsWith("\n" . '    <span class="t-key">implements</span> ', $html);
        self::assertSame(1, substr_count($html, 't-ext'));
        self::assertSame(1, substr_count($html, 'title="ArrayAccess"'));
        self::assertSame('', (new SignatureHtml())->parentClause($services, 'extends', [], [], $context));
    }

    public function testTemplateListRendersBoundAndBareTemplates(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * @template T of object
 * @template U
 */
class Box
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Box.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Box.php', false);
        $box = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($box);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Box.html', 'Demo', [], ['T', 'U'], [], $table);
        $templates = $box->docBlock !== null ? $box->docBlock->templates : [];

        $html = (new SignatureHtml())->templateList($services, $templates, $context);

        self::assertSame('&lt;<span class="t-gen">T</span> <span class="t-key">of</span> <span class="t-key">object</span>, <span class="t-gen">U</span>&gt;', $html);
        self::assertSame('', (new SignatureHtml())->templateList($services, [], $context));
    }

    public function testMethodSignatureRendersSingleLineSignature(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    final public static function run(int $count): string
    {
        return (string) $count;
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        $html = (new SignatureHtml())->methodSignature($services, $widget->methods[0], $context);

        self::assertSame(
            '<span class="t-key">final</span> <span class="t-key">public</span> <span class="t-key">static</span> <span class="t-key">function</span> '
            . '<span class="sig-name">run</span>(<span class="t-key">int</span> <span class="t-var">$count</span>): <span class="t-key">string</span>',
            $html,
        );
    }

    public function testMethodSignatureWrapsLongParameterLists(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public function configure(string $firstVeryLongParameterName = 'first-default-value', string $secondVeryLongParameterName = 'second-default-value'): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        $html = (new SignatureHtml())->methodSignature($services, $widget->methods[0], $context);

        self::assertStringContainsString('<span class="sig-name">configure</span>(' . "\n" . '    <span class="t-key">string</span>', $html);
        self::assertStringContainsString(',' . "\n" . '): <span class="t-key">void</span>', $html);
        self::assertStringContainsString('<span class="t-var">$firstVeryLongParameterName</span>', $html);
        self::assertStringContainsString('<span class="t-var">$secondVeryLongParameterName</span>', $html);
    }

    public function testFunctionSignatureRendersNameParametersAndReturn(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

function make(int $count): string
{
    return (string) $count;
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/functions.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/functions.php', false);
        $table = new SymbolTable();
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/function.make.html', 'Demo', [], [], [], $table);

        $html = (new SignatureHtml())->functionSignature($services, $symbols->functions[0], $context);

        self::assertSame(
            '<span class="t-key">function</span> <span class="sig-name">make</span>(<span class="t-key">int</span> <span class="t-var">$count</span>): <span class="t-key">string</span>',
            $html,
        );
    }

    public function testCallableSignatureWrapsWhenLengthExceedsLimit(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public function configure(string $firstVeryLongParameterName = 'first-default-value', string $secondVeryLongParameterName = 'second-default-value'): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        $wrapped = (new SignatureHtml())->callableSignature($services, 'head', $widget->methods[0]->parameters, '', $context);

        self::assertStringStartsWith('head(' . "\n" . '    ', $wrapped);
        self::assertStringEndsWith(',' . "\n" . ')', $wrapped);
        self::assertSame('head(): x', (new SignatureHtml())->callableSignature($services, 'head', [], ': x', $context));
    }

    public function testParameterRendersPromotedVariadicAndByRefForms(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public function __construct(private int $count = 3)
    {
    }

    public function push(string ...$items): void
    {
    }

    public function swap(int &$value): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        self::assertSame(
            '<span class="t-key">private</span> <span class="t-key">int</span> <span class="t-var">$count</span> = <span class="t-lit">3</span>',
            (new SignatureHtml())->parameter($services, $widget->methods[0]->parameters[0], $context),
        );
        self::assertSame(
            '<span class="t-key">string</span> ...<span class="t-var">$items</span>',
            (new SignatureHtml())->parameter($services, $widget->methods[1]->parameters[0], $context),
        );
        self::assertSame(
            '<span class="t-key">int</span> &amp;<span class="t-var">$value</span>',
            (new SignatureHtml())->parameter($services, $widget->methods[2]->parameters[0], $context),
        );
    }

    public function testPropertySignatureRendersStaticNullableProperty(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    private static ?string $name = null;
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        $html = (new SignatureHtml())->propertySignature($services, $widget->properties[0], $context);

        self::assertSame(
            '<span class="t-key">private</span> <span class="t-key">static</span> ?<span class="t-key">string</span> '
            . '<span class="t-var">$name</span> = <span class="t-lit">null</span>',
            $html,
        );
    }

    public function testConstantSignatureRendersTypedConstant(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    /** @var non-empty-string */
    public const NAME = 'demo';
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        $html = (new SignatureHtml())->constantSignature($services, $widget->constants[0], $context);

        self::assertSame(
            '<span class="t-key">public</span> <span class="t-key">const</span> <span class="t-key">non-empty-string</span> '
            . '<span class="sig-name">NAME</span> = <span class="t-lit">&#039;demo&#039;</span>',
            $html,
        );
    }

    public function testCaseSignatureRendersBackedCase(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

enum Status: string
{
    case Active = 'active';
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Status.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Status.php', false);
        $status = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($status);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $html = (new SignatureHtml())->caseSignature($services, $status->enumCases[0]);

        self::assertSame(
            '<span class="t-key">case</span> <span class="sig-name">Active</span> = <span class="t-lit">&#039;active&#039;</span>',
            $html,
        );
    }

    public function testMethodSignatureMarksTheParametersOfAComparedDeclaration(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public function run(int $count, string $label): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $index = new DiffIndex('main', 'HEAD');
        $key = $index->keys()->member('Demo\Widget', DiffKey::METHOD, 'run');
        $index->mark($index->keys()->parameter($key, 'label'), DiffStatus::ADDED);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), $symbols->classLikes, [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model, $index);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        $html = (new SignatureHtml())->methodSignature($services, $widget->methods[0], $context, $key);

        self::assertStringContainsString('<span class="sig-param" data-diff="same"><span class="t-key">int</span> <span class="t-var">$count</span></span>', $html);
        self::assertStringContainsString('<span class="sig-param" data-diff="added"><span class="t-key">string</span> <span class="t-var">$label</span></span>', $html);
        self::assertStringNotContainsString('sig-plain', $html);
    }

    public function testParameterListRendersTheMergedListAndTheHeadOnlyList(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public function run(int $count, string $label): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $index = new DiffIndex('main', 'HEAD');
        $key = $index->keys()->member('Demo\Widget', DiffKey::METHOD, 'run');
        $index->mark($index->keys()->parameter($key, 'label'), DiffStatus::REMOVED);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), $symbols->classLikes, [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model, $index);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);
        $signature = new SignatureHtml();

        $merged = $signature->parameterList($services, '', $widget->methods[0]->parameters, '', $context, $key, false);
        $plain = $signature->parameterList($services, '', $widget->methods[0]->parameters, '', $context, $key, true);

        self::assertStringContainsString('data-diff="removed"', $merged);
        self::assertStringContainsString('$label', $merged);
        self::assertSame('(<span class="t-key">int</span> <span class="t-var">$count</span>)', $plain);
    }

    public function testMethodSignatureShowsBothFormsWhenTheHeadDroppedAParameter(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public function run(int $count, string $label): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $index = new DiffIndex('main', 'HEAD');
        $key = $index->keys()->member('Demo\Widget', DiffKey::METHOD, 'run');
        $index->mark($index->keys()->parameter($key, 'label'), DiffStatus::REMOVED);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), $symbols->classLikes, [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model, $index);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        $html = (new SignatureHtml())->methodSignature($services, $widget->methods[0], $context, $key);

        self::assertStringContainsString('<span class="sig-diff">', $html);
        self::assertStringContainsString('<span class="sig-plain">(<span class="t-key">int</span> <span class="t-var">$count</span>)</span>', $html);
    }

    public function testHasRemovedParameterFindsTheOnesTheHeadDropped(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public function run(int $count, string $label): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $index = new DiffIndex('main', 'HEAD');
        $key = $index->keys()->member('Demo\Widget', DiffKey::METHOD, 'run');
        $index->mark($index->keys()->parameter($key, 'label'), DiffStatus::REMOVED);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), $symbols->classLikes, [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model, $index);
        $signature = new SignatureHtml();

        self::assertTrue($signature->hasRemovedParameter($services, $widget->methods[0]->parameters, $key));
        self::assertFalse($signature->hasRemovedParameter($services, $widget->methods[0]->parameters, ''));
        self::assertFalse($signature->hasRemovedParameter($services, [], $key));
    }

    public function testIsRemovedParameterAnswersOnlyInsideAComparison(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public function run(int $count, string $label): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $index = new DiffIndex('main', 'HEAD');
        $key = $index->keys()->member('Demo\Widget', DiffKey::METHOD, 'run');
        $index->mark($index->keys()->parameter($key, 'label'), DiffStatus::REMOVED);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), $symbols->classLikes, [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $signature = new SignatureHtml();

        self::assertTrue($signature->isRemovedParameter((new SiteRenderer())->services($model, $index), $widget->methods[0]->parameters[1], $key));
        self::assertFalse($signature->isRemovedParameter((new SiteRenderer())->services($model, $index), $widget->methods[0]->parameters[0], $key));
        self::assertFalse($signature->isRemovedParameter((new SiteRenderer())->services($model), $widget->methods[0]->parameters[1], $key));
    }

    public function testMarkedParameterCarriesTheStateOfOneParameter(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public function run(int $count): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $index = new DiffIndex('main', 'HEAD');
        $key = $index->keys()->member('Demo\Widget', DiffKey::METHOD, 'run');
        $index->mark($index->keys()->parameter($key, 'count'), DiffStatus::MODIFIED);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), $symbols->classLikes, [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $signature = new SignatureHtml();
        $parameter = $widget->methods[0]->parameters[0];

        self::assertSame(
            '<span class="sig-param" data-diff="modified">RENDERED</span>',
            $signature->markedParameter((new SiteRenderer())->services($model, $index), $parameter, 'RENDERED', $key),
        );
        self::assertSame('RENDERED', $signature->markedParameter((new SiteRenderer())->services($model, $index), $parameter, 'RENDERED', ''));
        self::assertSame('RENDERED', $signature->markedParameter((new SiteRenderer())->services($model), $parameter, 'RENDERED', $key));
    }
}
