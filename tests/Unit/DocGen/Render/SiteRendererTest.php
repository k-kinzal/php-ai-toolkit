<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind;
use PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc;
use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\MarkdownDoc;
use PhpAiToolkit\DocGen\Analysis\Model\MethodDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Render\AssetPublisher;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\MarkdownLinks;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\Page\AllItemsPage;
use PhpAiToolkit\DocGen\Render\Page\BreadcrumbHtml;
use PhpAiToolkit\DocGen\Render\Page\ClassLikePage;
use PhpAiToolkit\DocGen\Render\Page\DocTextHtml;
use PhpAiToolkit\DocGen\Render\Page\DocumentListHtml;
use PhpAiToolkit\DocGen\Render\Page\DocumentPage;
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

#[CoversClass(SiteRenderer::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(ExampleHtml::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(FunctionPage::class)]
#[UsesClass(GraphSvg::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(IndexPage::class)]
#[UsesClass(LayerPage::class)]
#[UsesClass(MarkdownDoc::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownLinks::class)]
#[UsesClass(DocumentListHtml::class)]
#[UsesClass(DocumentPage::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(MemberHtml::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NamespacePage::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PackagePage::class)]
#[UsesClass(PageChrome::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RelationsHtml::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SignatureHtml::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseHtml::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeRenderContext::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UsageListHtml::class)]
final class SiteRendererTest extends TestCase
{
    public function testRenderWritesCompleteSite(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-render-' . uniqid('', true);
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/src/Widget.php', "<?php\n\nnamespace Demo;\n\nfinal class Widget\n{\n}\n");
        file_put_contents($dir . '/src/Helper.php', "<?php\n\nnamespace Demo;\n\nfinal class Helper\n{\n}\n");
        file_put_contents($dir . '/README.md', "# Demo\n\nHello readme.\n");
        $summary = new DocBlock('Widget summary.', '', [], null, null, [], [], [], [], [], [], null, false, '/** */');
        $widget = new ClassLikeDoc(
            'Demo\Widget',
            'Widget',
            'Demo',
            'class',
            'demo/pkg',
            'src/Widget.php',
            5,
            7,
            false,
            true,
            [],
            [],
            [],
            [new ConstantDoc('LIMIT', 'public', '10', null, 6)],
            [],
            [new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('void', null), null, 6, 7)],
            [],
            null,
            $summary,
            [],
            false,
        );
        $helper = new ClassLikeDoc('Demo\Helper', 'Helper', 'Demo', 'class', 'demo/pkg', 'src/Helper.php', 5, 7, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $table->registerClassLike($helper);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([$widget, $helper]);
        $usages = new UsageIndex();
        $usages->build([]);
        $manifest = new ComposerManifest($dir, 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []);
        $model = new ProjectModel('Demo Docs', $dir, [new DiscoveredPackage($manifest, false)], new PackageGraph([]), [$widget, $helper], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $out = $dir . '/site';

        self::assertSame(8, (new SiteRenderer())->render($model, $out));
        self::assertFileExists($out . '/index.html');
        self::assertFileExists($out . '/demo/pkg/index.html');
        self::assertFileExists($out . '/demo/pkg/all-items.html');
        self::assertFileExists($out . '/demo/pkg/Demo/index.html');
        self::assertFileExists($out . '/demo/pkg/Demo/class.Widget.html');
        self::assertFileExists($out . '/demo/pkg/Demo/class.Helper.html');
        self::assertFileExists($out . '/src/src/Widget.php.html');
        self::assertFileExists($out . '/src/src/Helper.php.html');
        self::assertFileExists($out . '/assets/style.css');
        self::assertFileExists($out . '/assets/app.js');
        self::assertFileExists($out . '/assets/search-index.js');
        self::assertFileExists($out . '/.nojekyll');
    }

    public function testRenderPackagePagesWritesPackageAndNamespaceIndexes(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-render-' . uniqid('', true);
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 5, 7, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $manifest = new ComposerManifest($dir, 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []);
        $model = new ProjectModel('Demo Docs', $dir, [new DiscoveredPackage($manifest, false)], new PackageGraph([]), [$widget], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, ['demo\\widget' => ['Domain']], null, []);
        $out = $dir . '/site';
        $renderer = new SiteRenderer();

        self::assertSame(4, $renderer->renderPackagePages($renderer->services($model), $model, $out));
        self::assertFileExists($out . '/demo/pkg/index.html');
        self::assertFileExists($out . '/demo/pkg/all-items.html');
        self::assertFileExists($out . '/demo/pkg/layer.Domain.html');
        self::assertFileExists($out . '/demo/pkg/Demo/index.html');
    }

    public function testRenderDocumentPagesWritesOnePagePerReadableDocument(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-render-' . uniqid('', true);
        mkdir($dir . '/docs', 0777, true);
        file_put_contents($dir . '/docs/guide.md', "# Guide\n\nHello guide.\n");
        $manifest = new ComposerManifest($dir, 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []);
        $documents = [
            new MarkdownDoc('demo/pkg', 'docs/guide.md', 'docs/guide.md', 'Guide'),
            new MarkdownDoc('demo/pkg', 'docs/absent.md', 'docs/absent.md', 'Absent'),
        ];
        $model = new ProjectModel('Demo Docs', $dir, [new DiscoveredPackage($manifest, false)], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], $documents);
        $out = $dir . '/site';
        $renderer = new SiteRenderer();

        self::assertSame(1, $renderer->renderDocumentPages($renderer->services($model), $model, $out));
        self::assertFileExists($out . '/demo/pkg/doc/docs/guide.md.html');
        self::assertFileDoesNotExist($out . '/demo/pkg/doc/docs/absent.md.html');
        self::assertStringContainsString('Hello guide.', (string) file_get_contents($out . '/demo/pkg/doc/docs/guide.md.html'));
    }

    public function testServicesBuildsRenderKitForModel(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();

        $kit = $renderer->services($model);
        $again = $renderer->services($model);

        self::assertSame($model, $kit->model);
        self::assertNotSame($kit, $again);
        self::assertSame($kit->url, $again->url);
        self::assertNotSame($kit->escaper, $again->escaper);
        self::assertSame('demo/pkg/index.html', $kit->url->packagePage('demo/pkg'));
    }

    public function testReadmeReturnsNullWhenAbsent(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-readme-' . uniqid('', true);
        mkdir($dir, 0777, true);
        $manifest = new ComposerManifest($dir, 'demo/pkg', '', [], [], [], [], []);

        self::assertNull((new SiteRenderer())->readme(new DiscoveredPackage($manifest, false)));
    }

    public function testReadmeReturnsContentsWhenPresent(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-readme-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/README.md', "# Demo\n\nHello readme.\n");
        $manifest = new ComposerManifest($dir, 'demo/pkg', '', [], [], [], [], []);

        self::assertSame("# Demo\n\nHello readme.\n", (new SiteRenderer())->readme(new DiscoveredPackage($manifest, false)));
    }

    public function testNamespacesOfListsSortedNonDevNamespaces(): void
    {
        $acme = new ClassLikeDoc('Acme\A', 'A', 'Acme', 'class', 'demo/pkg', 'src/A.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $demo = new ClassLikeDoc('Demo\B', 'B', 'Demo', 'class', 'demo/pkg', 'src/B.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $dev = new ClassLikeDoc('Devs\C', 'C', 'Devs', 'class', 'demo/pkg', 'tests/C.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], true);
        $other = new ClassLikeDoc('Other\D', 'D', 'Other', 'class', 'other/pkg', 'src/D.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $function = new FunctionDoc('Zeta\greet', 'greet', 'Zeta', 'demo/pkg', 'src/fn.php', 1, 2, [], new TypeSignature(null, null), null, [], false);
        $model = new ProjectModel('T', '/tmp/docgen-root', [], new PackageGraph([]), [$demo, $acme, $dev, $other], [$function], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);

        self::assertSame(['Acme', 'Demo', 'Zeta'], (new SiteRenderer())->namespacesOf($model, 'demo/pkg'));
    }

    public function testSourceFilesDeduplicatesAndSorts(): void
    {
        $first = new ClassLikeDoc('Demo\A', 'A', 'Demo', 'class', 'demo/pkg', 'src/B.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $second = new ClassLikeDoc('Demo\B', 'B', 'Demo', 'class', 'demo/pkg', 'src/A.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $third = new ClassLikeDoc('Demo\C', 'C', 'Demo', 'class', 'demo/pkg', 'src/B.php', 3, 4, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $dev = new ClassLikeDoc('Demo\D', 'D', 'Demo', 'class', 'demo/pkg', 'tests/C.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], true);
        $function = new FunctionDoc('Demo\greet', 'greet', 'Demo', 'demo/pkg', 'src/fn.php', 1, 2, [], new TypeSignature(null, null), null, [], false);
        $model = new ProjectModel('T', '/tmp/docgen-root', [], new PackageGraph([]), [$first, $second, $third, $dev], [$function], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);

        self::assertSame(['src/A.php', 'src/B.php', 'src/fn.php', 'tests/C.php'], (new SiteRenderer())->sourceFiles($model));
    }
}
