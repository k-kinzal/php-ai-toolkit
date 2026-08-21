<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffLine;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\LcsMatcher;
use PhpAiToolkit\DocGen\Analysis\Diff\LineDiffer;
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
use PhpAiToolkit\DocGen\Cache\CachedPageWriter;
use PhpAiToolkit\DocGen\Cache\CacheStore;
use PhpAiToolkit\DocGen\Cache\PageRecord;
use PhpAiToolkit\DocGen\Cache\RenderCache;
use PhpAiToolkit\DocGen\Cache\ToolkitFingerprint;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Parallel\CpuCoreCounter;
use PhpAiToolkit\DocGen\Parallel\WorkerCount;
use PhpAiToolkit\DocGen\Parallel\WorkerPool;
use PhpAiToolkit\DocGen\Parallel\WorkScheduler;
use PhpAiToolkit\DocGen\Render\AssetPublisher;
use PhpAiToolkit\DocGen\Render\Diff\DiffBanner;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PhpAiToolkit\DocGen\Render\Diff\DiffModeControl;
use PhpAiToolkit\DocGen\Render\Diff\MarkdownDiffHtml;
use PhpAiToolkit\DocGen\Render\Diff\SourceDiffHtml;
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
use PhpAiToolkit\DocGen\Render\Page\PrivateSurfaceHtml;
use PhpAiToolkit\DocGen\Render\Page\RelationsHtml;
use PhpAiToolkit\DocGen\Render\Page\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\SidebarScope;
use PhpAiToolkit\DocGen\Render\Page\SignatureHtml;
use PhpAiToolkit\DocGen\Render\Page\SourcePage;
use PhpAiToolkit\DocGen\Render\Page\SymbolDescription;
use PhpAiToolkit\DocGen\Render\Page\SymbolIndex;
use PhpAiToolkit\DocGen\Render\Page\SymbolListHtml;
use PhpAiToolkit\DocGen\Render\Page\SymbolRow;
use PhpAiToolkit\DocGen\Render\Page\TestCaseHtml;
use PhpAiToolkit\DocGen\Render\Page\UsageListHtml;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\RepositoryLink;
use PhpAiToolkit\DocGen\Render\SearchIndexBuilder;
use PhpAiToolkit\DocGen\Render\Signature\PageSignature;
use PhpAiToolkit\DocGen\Render\Signature\SidebarDigest;
use PhpAiToolkit\DocGen\Render\Signature\SourceDigestIndex;
use PhpAiToolkit\DocGen\Render\Signature\SymbolReferenceScanner;
use PhpAiToolkit\DocGen\Render\SitePages;
use PhpAiToolkit\DocGen\Render\SiteRenderer;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\SocialCard;
use PhpAiToolkit\DocGen\Render\SocialMeta;
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
#[UsesClass(CacheStore::class)]
#[UsesClass(CachedPageWriter::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(CpuCoreCounter::class)]
#[UsesClass(DiffBanner::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffLine::class)]
#[UsesClass(DiffModeControl::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentListHtml::class)]
#[UsesClass(DocumentPage::class)]
#[UsesClass(ExampleHtml::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(FunctionPage::class)]
#[UsesClass(GraphSvg::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(IndexPage::class)]
#[UsesClass(LayerPage::class)]
#[UsesClass(LcsMatcher::class)]
#[UsesClass(LineDiffer::class)]
#[UsesClass(MarkdownDiffHtml::class)]
#[UsesClass(MarkdownDoc::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownLinks::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(MemberHtml::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NamespacePage::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PackagePage::class)]
#[UsesClass(PageChrome::class)]
#[UsesClass(PageRecord::class)]
#[UsesClass(PageSignature::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(PrivateSurfaceHtml::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RelationsHtml::class)]
#[UsesClass(RenderCache::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(RepositoryLink::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarDigest::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SignatureHtml::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SitePages::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SourceDiffHtml::class)]
#[UsesClass(SourceDigestIndex::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolDescription::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolReferenceScanner::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseHtml::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(ToolkitFingerprint::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeRenderContext::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UsageListHtml::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
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

        self::assertCount(4, $renderer->renderPackagePages($renderer->services($model), $model, $out, new CachedPageWriter()));
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

        self::assertCount(1, $renderer->renderDocumentPages($renderer->services($model), $model, $out, new CachedPageWriter()));
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

    public function testRenderSourcePagesReadsEachFileFromTheRevisionThatHasIt(): void
    {
        $head = sys_get_temp_dir() . '/docgen-render-head-' . bin2hex(random_bytes(4));
        $base = sys_get_temp_dir() . '/docgen-render-base-' . bin2hex(random_bytes(4));
        $out = sys_get_temp_dir() . '/docgen-render-out-' . bin2hex(random_bytes(4));
        mkdir($head . '/src', 0777, true);
        mkdir($base . '/src', 0777, true);
        file_put_contents($head . '/src/Kept.php', '<?php');
        file_put_contents($base . '/src/Kept.php', '<?php');
        file_put_contents($base . '/src/Gone.php', '<?php');
        $kept = new ClassLikeDoc('Demo\Kept', 'Kept', 'Demo', 'class', 'demo/pkg', 'src/Kept.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $gone = new ClassLikeDoc('Demo\Gone', 'Gone', 'Demo', 'class', 'demo/pkg', 'src/Gone.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $absent = new ClassLikeDoc('Demo\Absent', 'Absent', 'Demo', 'class', 'demo/pkg', 'src/Absent.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $model = new ProjectModel('T', $head, [], new PackageGraph([]), [$kept, $gone, $absent], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();

        $records = $renderer->renderSourcePages($renderer->services($model, new DiffIndex('main', 'HEAD', $base)), $model, $out, new CachedPageWriter());

        self::assertCount(2, $records);
        self::assertFileExists($out . '/src/src/Kept.php.html');
        self::assertFileExists($out . '/src/src/Gone.php.html');
        self::assertFileDoesNotExist($out . '/src/src/Absent.php.html');
    }

    public function testRenderClassLikePagesWritesOnePagePerDocumentedSymbol(): void
    {
        $out = sys_get_temp_dir() . '/docgen-render-out-' . bin2hex(random_bytes(4));
        $engine = new ClassLikeDoc('Demo\Engine', 'Engine', 'Demo', 'class', 'demo/pkg', 'src/Engine.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $probe = new ClassLikeDoc('Demo\Probe', 'Probe', 'Demo', 'class', 'demo/pkg', 'tests/Probe.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], true);
        $model = new ProjectModel('T', '/tmp/none', [], new PackageGraph([]), [$engine, $probe], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();

        $records = $renderer->renderClassLikePages($renderer->services($model), $model, $out, new CachedPageWriter(), 2);

        self::assertCount(1, $records);
        self::assertFileExists($out . '/demo/pkg/Demo/class.Engine.html');
        self::assertFileDoesNotExist($out . '/demo/pkg/Demo/class.Probe.html');
    }

    public function testWriteSourcePagesSkipsFilesNoRevisionHas(): void
    {
        $root = sys_get_temp_dir() . '/docgen-render-src-' . bin2hex(random_bytes(4));
        $out = sys_get_temp_dir() . '/docgen-render-out-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Kept.php', '<?php');
        $model = new ProjectModel('T', $root, [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();

        $records = $renderer->writeSourcePages($renderer->services($model), $root, $out, new CachedPageWriter(), ['src/Kept.php', 'src/Absent.php']);

        self::assertCount(1, $records);
        self::assertFileExists($out . '/src/src/Kept.php.html');
        self::assertFileDoesNotExist($out . '/src/src/Absent.php.html');
    }

    public function testRenderFunctionPagesWritesOnePagePerDocumentedFunction(): void
    {
        $out = sys_get_temp_dir() . '/docgen-render-out-' . bin2hex(random_bytes(4));
        $greet = new FunctionDoc('Demo\\greet', 'greet', 'Demo', 'demo/pkg', 'src/fn.php', 1, 2, [], new TypeSignature(null, null), null, [], false);
        $probe = new FunctionDoc('Demo\\probe', 'probe', 'Demo', 'demo/pkg', 'tests/fn.php', 1, 2, [], new TypeSignature(null, null), null, [], true);
        $model = new ProjectModel('T', '/tmp/none', [], new PackageGraph([]), [], [$greet, $probe], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();

        $records = $renderer->renderFunctionPages($renderer->services($model), $model, $out, new CachedPageWriter());

        self::assertCount(1, $records);
        self::assertFileExists($out . '/demo/pkg/Demo/function.greet.html');
        self::assertFileDoesNotExist($out . '/demo/pkg/Demo/function.probe.html');
    }

    public function testNamespacePagesSkipsTheGlobalNamespace(): void
    {
        $out = sys_get_temp_dir() . '/docgen-render-out-' . bin2hex(random_bytes(4));
        $global = new ClassLikeDoc('Loose', 'Loose', '', 'class', 'demo/pkg', 'src/Loose.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $scoped = new ClassLikeDoc('Demo\\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $model = new ProjectModel('T', '/tmp/none', [], new PackageGraph([]), [$global, $scoped], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();

        $records = $renderer->namespacePages($renderer->services($model), $model, $out, new CachedPageWriter(), 'demo/pkg');

        self::assertCount(1, $records);
        self::assertSame('demo/pkg/Demo/index.html', $records[0]->path);
    }

    public function testRenderLeavesUnchangedPagesAloneAndRemovesVanishedOnes(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-render-cache-' . bin2hex(random_bytes(4));
        mkdir($dir . '/src', 0777, true);
        file_put_contents($dir . '/src/Widget.php', "<?php\n\nnamespace Demo;\n\nfinal class Widget\n{\n}\n");
        $widget = new ClassLikeDoc('Demo\\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 5, 7, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $gone = new ClassLikeDoc('Demo\\Gone', 'Gone', 'Demo', 'class', 'demo/pkg', 'src/Gone.php', 5, 7, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $manifest = new ComposerManifest($dir, 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []);
        $package = new DiscoveredPackage($manifest, false);
        $full = new ProjectModel('Demo Docs', $dir, [$package], new PackageGraph([]), [$widget, $gone], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $smaller = new ProjectModel('Demo Docs', $dir, [$package], new PackageGraph([]), [$widget], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $out = $dir . '/site';
        $cache = new RenderCache($dir . '/cache', $out);
        $cache->load();
        $renderer = new SiteRenderer();

        $renderer->render($full, $out, null, 1, $cache);
        $written = (string) file_get_contents($out . '/src/src/Widget.php.html');
        $again = new RenderCache($dir . '/cache', $out);
        $again->load();
        $renderer->render($smaller, $out, null, 1, $again);

        self::assertSame(1, $again->reused());
        self::assertSame(5, $again->rendered());
        self::assertSame($written, file_get_contents($out . '/src/src/Widget.php.html'));
        self::assertFileDoesNotExist($out . '/demo/pkg/Demo/class.Gone.html');
    }
}
