<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind;
use PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc;
use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PhpAiToolkit\DocGen\Analysis\Model\DocTag;
use PhpAiToolkit\DocGen\Analysis\Model\EnumCaseDoc;
use PhpAiToolkit\DocGen\Analysis\Model\MethodDoc;
use PhpAiToolkit\DocGen\Analysis\Model\PropertyDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TemplateDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeAliasDoc;
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
use PhpAiToolkit\DocGen\Analysis\Reference\TestCase as ReferenceTestCase;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\Usage;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Render\AssetPublisher;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\Page\AllItemsPage;
use PhpAiToolkit\DocGen\Render\Page\BreadcrumbHtml;
use PhpAiToolkit\DocGen\Render\Page\ClassLikePage;
use PhpAiToolkit\DocGen\Render\Page\DocTextHtml;
use PhpAiToolkit\DocGen\Render\Page\ExampleHtml;
use PhpAiToolkit\DocGen\Render\Page\FunctionPage;
use PhpAiToolkit\DocGen\Render\Page\GraphSvg;
use PhpAiToolkit\DocGen\Render\Page\IndexPage;
use PhpAiToolkit\DocGen\Render\Page\LayerPage;
use PhpAiToolkit\DocGen\Render\Page\MemberHtml;
use PhpAiToolkit\DocGen\Render\Page\NamespacePage;
use PhpAiToolkit\DocGen\Render\Page\PackagePage;
use PhpAiToolkit\DocGen\Render\Page\RelationsHtml;
use PhpAiToolkit\DocGen\Render\Page\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\SidebarScope;
use PhpAiToolkit\DocGen\Render\Page\SignatureHtml;
use PhpAiToolkit\DocGen\Render\Page\SourcePage;
use PhpAiToolkit\DocGen\Render\Page\SymbolIndex;
use PhpAiToolkit\DocGen\Render\Page\SymbolListHtml;
use PhpAiToolkit\DocGen\Render\Page\SymbolRow;
use PhpAiToolkit\DocGen\Render\Page\TestCaseHtml;
use PhpAiToolkit\DocGen\Render\Page\UsageListHtml;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\SearchIndexBuilder;
use PhpAiToolkit\DocGen\Render\SiteRenderer;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PhpAiToolkit\DocGen\Render\TypeRenderContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassLikePage::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocTag::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(EnumCaseDoc::class)]
#[UsesClass(ExampleHtml::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(FunctionPage::class)]
#[UsesClass(GraphSvg::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(IndexPage::class)]
#[UsesClass(LayerPage::class)]
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
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(ReferenceTestCase::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyDoc::class)]
#[UsesClass(RelationsHtml::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SignatureHtml::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TemplateDoc::class)]
#[UsesClass(TestCaseHtml::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeAliasDoc::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeRenderContext::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(Usage::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UsageListHtml::class)]
#[UsesClass(UseMapCollector::class)]
final class ClassLikePageTest extends TestCase
{
    public function testRenderProducesCompleteDocument(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * Widget summary line.
 */
final class Widget
{
    public function run(): int
    {
        return 1;
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

        $html = (new ClassLikePage())->render($services, $widget);

        self::assertStringStartsWith('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<title>Widget — Demo Docs</title>', $html);
        self::assertStringContainsString('<h1><span class="chip chip-kind k-class">class</span>Widget</h1>', $html);
        self::assertStringContainsString(
            '<a href="../../../demo/pkg/index.html">demo/pkg</a><span class="crumb-sep">::</span>'
            . '<a href="../../../demo/pkg/Demo/index.html">Demo</a><span class="crumb-sep">::</span>'
            . '<span class="crumb-current">Widget</span>',
            $html,
        );
        self::assertStringContainsString('<p class="lede">Widget summary line.</p>', $html);
        self::assertStringContainsString('<h2 id="methods">', $html);
    }

    public function testContextBuildsTemplatesAliasesAndScope(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * Widget summary line.
 *
 * @template T of object
 * @phpstan-type Row array{id: int}
 */
final class Widget
{
}

class Plain
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $plain = $symbols->classLikes[1];
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
        $pagePath = 'demo/pkg/Demo/class.Widget.html';

        $context = (new ClassLikePage())->context($services, $pagePath, $widget, []);

        self::assertSame(['T'], $context->templates);
        self::assertSame(['Row' => '#alias.Row'], $context->aliases);
        self::assertSame($pagePath, $context->pagePath);
        self::assertSame('Demo', $context->namespace);

        $extra = $widget->docBlock !== null ? $widget->docBlock->templates : [];

        self::assertSame(['T'], (new ClassLikePage())->context($services, $pagePath, $plain, $extra)->templates);
    }

    public function testContentRendersHeadSignatureDocsAndLayerBadges(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * Widget summary line.
 */
final class Widget
{
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
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, ['demo\widget' => ['Domain']], null, []);
        $services = (new SiteRenderer())->services($model);
        $pagePath = 'demo/pkg/Demo/class.Widget.html';
        $context = new TypeRenderContext($pagePath, 'Demo', [], [], [], $table);

        $html = (new ClassLikePage())->content($services, $pagePath, $widget, $context);

        self::assertStringContainsString(
            '<a class="chip chip-layer" href="../../../demo/pkg/layer.Domain.html" title="deptrac layer">Domain</a>',
            $html,
        );
        self::assertStringContainsString(
            '<a class="src-link" href="../../../src/src/Demo/Widget.php.html#L' . $widget->startLine . '">src/Demo/Widget.php:' . $widget->startLine . '</a>',
            $html,
        );
        self::assertStringContainsString('<pre class="signature"><code>', $html);
        self::assertStringContainsString('<p class="lede">Widget summary line.</p>', $html);
    }

    public function testAliasSectionRendersTypeAliasDefinitions(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * Widget summary line.
 *
 * @phpstan-type Row array{id: int}
 */
final class Widget
{
}

class Plain
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $plain = $symbols->classLikes[1];
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
        $pagePath = 'demo/pkg/Demo/class.Widget.html';
        $context = (new ClassLikePage())->context($services, $pagePath, $widget, []);

        $html = (new ClassLikePage())->aliasSection($services, $pagePath, $widget, $context);

        self::assertStringContainsString('<h2 id="aliases">Type Aliases<a class="anchor" href="#aliases">§</a></h2>', $html);
        self::assertStringContainsString(
            '<div class="member alias-def" id="alias.Row"><pre class="member-sig"><code><span class="t-alias">Row</span> = '
            . '<span class="t-key">array</span>{<span class="t-shape-key">id</span>: <span class="t-key">int</span>}</code></pre></div>',
            $html,
        );
        self::assertSame('', (new ClassLikePage())->aliasSection($services, $pagePath, $plain, $context));
    }

    public function testMemberSectionsRendersVisibleMembersAndPrivateSurface(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

final class Widget
{
    public const LIMIT = 3;

    private const SECRET = 'hidden';

    public int $count = 0;

    private string $token = '';

    public function run(): int
    {
        return $this->count;
    }

    private function seed(): void
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
        $pagePath = 'demo/pkg/Demo/class.Widget.html';
        $context = new TypeRenderContext($pagePath, 'Demo', [], [], [], $table);

        $html = (new ClassLikePage())->memberSections($services, $pagePath, $widget, $context);

        self::assertStringContainsString('<h2 id="constants">Constants<a class="anchor" href="#constants">§</a></h2>', $html);
        self::assertStringContainsString('id="constant.LIMIT"', $html);
        self::assertStringContainsString('<h2 id="properties">Properties<a class="anchor" href="#properties">§</a></h2>', $html);
        self::assertStringContainsString('id="property.count"', $html);
        self::assertStringContainsString('<h2 id="methods">Methods<a class="anchor" href="#methods">§</a></h2>', $html);
        self::assertStringContainsString('id="method.run"', $html);
        self::assertStringNotContainsString('id="constant.SECRET"', $html);
        self::assertStringNotContainsString('id="property.token"', $html);
        self::assertStringNotContainsString('id="method.seed"', $html);
        self::assertStringNotContainsString('<h2 id="cases">', $html);
        self::assertStringNotContainsString('private-surface', $html);
    }

    public function testMemberSectionsRendersEnumCases(): void
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
        $pagePath = 'demo/pkg/Demo/enum.Status.html';
        $context = new TypeRenderContext($pagePath, 'Demo', [], [], [], $table);

        $html = (new ClassLikePage())->memberSections($services, $pagePath, $status, $context);

        self::assertStringContainsString('<h2 id="cases">Cases<a class="anchor" href="#cases">§</a></h2>', $html);
        self::assertStringContainsString('id="case.Active"', $html);
    }

    public function testMethodSectionRendersNonPrivateMethodsOnly(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

final class Widget
{
    public function run(): int
    {
        return 1;
    }

    private function seed(): void
    {
    }
}

class Plain
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $plain = $symbols->classLikes[1];
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
        $pagePath = 'demo/pkg/Demo/class.Widget.html';

        $html = (new ClassLikePage())->methodSection($services, $pagePath, $widget);

        self::assertStringStartsWith('<section><h2 id="methods">Methods<a class="anchor" href="#methods">§</a></h2>', $html);
        self::assertStringContainsString('id="method.run"', $html);
        self::assertStringNotContainsString('seed', $html);
        self::assertSame('', (new ClassLikePage())->methodSection($services, $pagePath, $plain));
    }

    public function testPrivateSurfaceListsOnlyPrivateMembers(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

final class Widget
{
    public const LIMIT = 3;

    private const SECRET = 'hidden';

    private string $token = '';

    private function seed(): void
    {
    }
}

class Plain
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $plain = $symbols->classLikes[1];
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

        $html = (new ClassLikePage())->privateSurface($services, $widget, $context);

        self::assertStringStartsWith(
            '<section class="private-surface"><h2 id="private-surface">Private surface <span class="count">3</span>'
            . '<a class="anchor" href="#private-surface">§</a></h2>'
            . '<p class="section-note">Implementation details, listed for orientation only.</p>'
            . '<pre class="member-sig private-sig"><code>',
            $html,
        );
        self::assertStringContainsString('<span class="sig-name">SECRET</span>', $html);
        self::assertStringContainsString('<span class="t-var">$token</span>', $html);
        self::assertStringContainsString('<span class="sig-name">seed</span>', $html);
        self::assertStringNotContainsString('<span class="sig-name">LIMIT</span>', $html);
        self::assertSame('', (new ClassLikePage())->privateSurface($services, $plain, $context));
    }

    public function testSectionsListsPresentSectionsInPageOrder(): void
    {
        $constants = [new ConstantDoc('LIMIT', 'public', '3', null, 6), new ConstantDoc('SECRET', 'private', "'x'", null, 8)];
        $properties = [new PropertyDoc('count', 'public', false, false, new TypeSignature('int', null), '0', null, 10)];
        $methods = [new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('int', null), null, 12, 15), new MethodDoc('seed', 'private', false, false, false, [], new TypeSignature('void', null), null, 17, 19)];
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 5, 20, false, true, [], [], [], $constants, $properties, $methods, [], null, null, [], false);
        $testCases = new TestCaseIndex();
        $testCases->record('Demo\Widget', null, new ReferenceTestCase('Tests\WidgetTest', 'testRun', null, null, ReferenceTestCase::ORIGIN_CALL));
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), $testCases, null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame([
            ['id' => 'constants', 'label' => 'Constants'],
            ['id' => 'properties', 'label' => 'Properties'],
            ['id' => 'methods', 'label' => 'Methods'],
            ['id' => 'private-surface', 'label' => 'Private surface'],
            ['id' => 'test-cases', 'label' => 'Test cases'],
            ['id' => 'relations', 'label' => 'Relations'],
        ], (new ClassLikePage())->sections($services, $widget));
    }

    public function testSectionsKeepsOnlyRelationsForABareSymbol(): void
    {
        $bare = new ClassLikeDoc('Demo\Bare', 'Bare', 'Demo', 'class', 'demo/pkg', 'src/Demo/Bare.php', 5, 8, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$bare], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame([['id' => 'relations', 'label' => 'Relations']], (new ClassLikePage())->sections($services, $bare));
    }

    public function testVisibleMembersDropsPrivateMembers(): void
    {
        $members = [
            new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('int', null), null, 12, 15),
            new MethodDoc('boot', 'protected', false, false, false, [], new TypeSignature('void', null), null, 17, 19),
            new MethodDoc('seed', 'private', false, false, false, [], new TypeSignature('void', null), null, 21, 23),
        ];

        $visible = (new ClassLikePage())->visibleMembers($members);

        self::assertCount(2, $visible);
        self::assertSame('run', $visible[0]->name);
        self::assertSame('boot', $visible[1]->name);
        self::assertSame([], (new ClassLikePage())->visibleMembers([]));
    }

    public function testPrivateMembersCollectsConstantsPropertiesAndMethodsInThatOrder(): void
    {
        $constants = [new ConstantDoc('LIMIT', 'public', '3', null, 6), new ConstantDoc('SECRET', 'private', "'x'", null, 8)];
        $properties = [new PropertyDoc('token', 'private', false, false, new TypeSignature('string', null), "''", null, 10)];
        $methods = [new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('int', null), null, 12, 15), new MethodDoc('seed', 'private', false, false, false, [], new TypeSignature('void', null), null, 17, 19)];
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 5, 20, false, true, [], [], [], $constants, $properties, $methods, [], null, null, [], false);

        $private = (new ClassLikePage())->privateMembers($widget);

        self::assertCount(3, $private);
        self::assertSame('SECRET', $private[0]->name);
        self::assertSame('token', $private[1]->name);
        self::assertSame('seed', $private[2]->name);
    }

    public function testTestCaseSectionSplitsDedicatedTestsFromOtherTests(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $bare = new ClassLikeDoc('Demo\Bare', 'Bare', 'Demo', 'class', 'demo/pkg', 'src/Demo/Bare.php', 5, 8, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $testCases = new TestCaseIndex();
        $testCases->build([
            new Usage('Demo\Widget', 'run', 'method-call', 'Tests\WidgetTest', 'testRun', 'tests/WidgetTest.php', 20, true),
            new Usage('Demo\Widget', 'run', 'method-call', 'Tests\AppTest', 'testBoot', 'tests/AppTest.php', 33, true),
        ], [$widget], null);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), $testCases, null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new ClassLikePage())->testCaseSection($services, 'demo/pkg/Demo/class.Widget.html', $widget);

        self::assertStringStartsWith(
            '<section><h2 id="test-cases">Test cases <span class="count">2</span><a class="anchor" href="#test-cases">§</a></h2>'
            . '<p class="section-note">Test cases that cover or call this symbol, from the coverage report and from the analyzed test sources.</p>'
            . '<details class="usage-details test-cases" open><summary>Dedicated tests <span class="count">1</span></summary><ul class="usage-list">'
            . '<li><a href="../../../src/tests/WidgetTest.php.html#L20" title="Tests\WidgetTest"><code>WidgetTest::testRun</code></a>',
            $html,
        );
        self::assertStringContainsString(
            '<details class="usage-details test-cases"><summary>Other tests reaching this symbol <span class="count">1</span></summary><ul class="usage-list">'
            . '<li><a href="../../../src/tests/AppTest.php.html#L33" title="Tests\AppTest"><code>AppTest::testBoot</code></a>',
            $html,
        );
        self::assertStringEndsWith("</section>\n", $html);
        self::assertSame('', (new ClassLikePage())->testCaseSection($services, 'demo/pkg/Demo/class.Bare.html', $bare));
    }

    public function testIsDedicatedTestMatchesOnlyTheShortNameTestClass(): void
    {
        self::assertTrue((new ClassLikePage())->isDedicatedTest('Tests\Unit\Demo\WidgetTest', 'Widget'));
        self::assertTrue((new ClassLikePage())->isDedicatedTest('WidgetTest', 'Widget'));
        self::assertFalse((new ClassLikePage())->isDedicatedTest('Tests\Unit\Demo\AppTest', 'Widget'));
        self::assertFalse((new ClassLikePage())->isDedicatedTest('Tests\Unit\Demo\WidgetFactoryTest', 'Widget'));
    }
}
