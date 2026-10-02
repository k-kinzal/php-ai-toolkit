<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Signature;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Coverage\CoverageIndex;
use Toolkit\DocGen\Analysis\Coverage\MethodCoverage;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Diff\LineDiffer;
use Toolkit\DocGen\Analysis\Doctest\AssertionScanner;
use Toolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;
use Toolkit\DocGen\Analysis\Model\MarkdownDoc;
use Toolkit\DocGen\Analysis\Model\MethodDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\Usage;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Cache\ToolkitFingerprint;
use Toolkit\DocGen\Package\ComposerManifest;
use Toolkit\DocGen\Package\DiscoveredPackage;
use Toolkit\DocGen\Package\PackageGraph;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkerPool;
use Toolkit\DocGen\Parallel\WorkScheduler;
use Toolkit\DocGen\Render\AssetPublisher;
use Toolkit\DocGen\Render\Diff\DiffHtml;
use Toolkit\DocGen\Render\Diff\MarkdownDiffHtml;
use Toolkit\DocGen\Render\Diff\SourceDiffHtml;
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\MarkdownInline;
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\Page\AllItemsPage;
use Toolkit\DocGen\Render\Page\ClassLikePage;
use Toolkit\DocGen\Render\Page\Component\DocTextHtml;
use Toolkit\DocGen\Render\Page\Component\DocumentListHtml;
use Toolkit\DocGen\Render\Page\Component\GraphSvg;
use Toolkit\DocGen\Render\Page\Component\MemberHtml;
use Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml;
use Toolkit\DocGen\Render\Page\Component\RelationsHtml;
use Toolkit\DocGen\Render\Page\Component\SidebarHtml;
use Toolkit\DocGen\Render\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Render\Page\Component\SymbolRow;
use Toolkit\DocGen\Render\Page\DocumentPage;
use Toolkit\DocGen\Render\Page\FunctionPage;
use Toolkit\DocGen\Render\Page\IndexPage;
use Toolkit\DocGen\Render\Page\LayerPage;
use Toolkit\DocGen\Render\Page\NamespacePage;
use Toolkit\DocGen\Render\Page\PackagePage;
use Toolkit\DocGen\Render\Page\SidebarScope;
use Toolkit\DocGen\Render\Page\SourcePage;
use Toolkit\DocGen\Render\Page\SymbolIndex;
use Toolkit\DocGen\Render\PageChrome;
use Toolkit\DocGen\Render\PhpHighlighter;
use Toolkit\DocGen\Render\RenderKit;
use Toolkit\DocGen\Render\SearchIndexBuilder;
use Toolkit\DocGen\Render\Signature\PageSignature;
use Toolkit\DocGen\Render\Signature\SidebarDigest;
use Toolkit\DocGen\Render\Signature\SourceDigestIndex;
use Toolkit\DocGen\Render\Signature\SymbolReferenceScanner;
use Toolkit\DocGen\Render\SiteRenderer;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\Social\SocialCard;
use Toolkit\DocGen\Render\Social\SocialMeta;
use Toolkit\DocGen\Render\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Render\Signature\PageSignature
 * @uses \Toolkit\DocGen\Render\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\AssetPublisher
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Render\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Analysis\Coverage\CoverageIndex
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Render\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Render\Page\Component\DocumentListHtml
 * @uses \Toolkit\DocGen\Render\Page\DocumentPage
 * @uses \Toolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \Toolkit\DocGen\Render\Page\FunctionPage
 * @uses \Toolkit\DocGen\Render\Page\Component\GraphSvg
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Render\Page\IndexPage
 * @uses \Toolkit\DocGen\Render\Page\LayerPage
 * @uses \Toolkit\DocGen\Analysis\Diff\LineDiffer
 * @uses \Toolkit\DocGen\Render\Diff\MarkdownDiffHtml
 * @uses \Toolkit\DocGen\Analysis\Model\MarkdownDoc
 * @uses \Toolkit\DocGen\Render\MarkdownInline
 * @uses \Toolkit\DocGen\Render\MarkdownRenderer
 * @uses \Toolkit\DocGen\Render\Page\Component\MemberHtml
 * @uses \Toolkit\DocGen\Analysis\Coverage\MethodCoverage
 * @uses \Toolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \Toolkit\DocGen\Render\Page\NamespacePage
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Render\Page\PackagePage
 * @uses \Toolkit\DocGen\Render\PageChrome
 * @uses \Toolkit\DocGen\Render\PhpHighlighter
 * @uses \Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Render\Page\Component\RelationsHtml
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Render\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Render\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Render\Page\SidebarScope
 * @uses \Toolkit\DocGen\Render\SiteRenderer
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Render\Social\SocialCard
 * @uses \Toolkit\DocGen\Render\Social\SocialMeta
 * @uses \Toolkit\DocGen\Render\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Render\Signature\SourceDigestIndex
 * @uses \Toolkit\DocGen\Render\Page\SourcePage
 * @uses \Toolkit\DocGen\Render\Page\SymbolIndex
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Render\Signature\SymbolReferenceScanner
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolRow
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Cache\ToolkitFingerprint
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\Usage
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 */
#[CoversClass(PageSignature::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(CoverageIndex::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentListHtml::class)]
#[UsesClass(DocumentPage::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(FunctionPage::class)]
#[UsesClass(GraphSvg::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(IndexPage::class)]
#[UsesClass(LayerPage::class)]
#[UsesClass(LineDiffer::class)]
#[UsesClass(MarkdownDiffHtml::class)]
#[UsesClass(MarkdownDoc::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(MemberHtml::class)]
#[UsesClass(MethodCoverage::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NamespacePage::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PackagePage::class)]
#[UsesClass(PageChrome::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(PrivateSurfaceHtml::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RelationsHtml::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarDigest::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SourceDiffHtml::class)]
#[UsesClass(SourceDigestIndex::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolReferenceScanner::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(ToolkitFingerprint::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(Usage::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
final class PageSignatureTest extends TestCase
{
    public function testRunDigestsWhatEveryPageOfOneRunHasInCommon(): void
    {
        $renderer = new SiteRenderer();
        $model = new ProjectModel('Demo Docs', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renamed = new ProjectModel('Other Docs', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = $renderer->services($model);
        $signatures = new PageSignature();

        self::assertSame($signatures->run($services), $signatures->run($services));
        self::assertNotSame($signatures->run($services), $signatures->run($renderer->services($renamed)));
    }

    public function testRunDigestsTheRepositoryEveryPageLinksBackTo(): void
    {
        $renderer = new SiteRenderer();
        $model = new ProjectModel('Demo Docs', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $linked = new ProjectModel('Demo Docs', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, 'https://github.com/example/project');
        $signatures = new PageSignature();

        self::assertNotSame($signatures->run($renderer->services($model)), $signatures->run($renderer->services($linked)));
    }

    public function testOfDigestsThePartsAndTheNamesInThem(): void
    {
        $renderer = new SiteRenderer();
        $model = new ProjectModel('Demo Docs', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = $renderer->services($model);
        $signatures = new PageSignature();

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $signatures->of($services, ['a']));
        self::assertSame($signatures->of($services, ['a', 'b']), $signatures->of($services, ['a', 'b']));
        self::assertNotSame($signatures->of($services, ['a', 'b']), $signatures->of($services, ['a', 'c']));
    }

    public function testEveryKindOfPageHasASignatureOfItsOwn(): void
    {
        $root = sys_get_temp_dir() . '/docgen-signature-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Widget.php', '<?php class Widget {}');
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $greet = new FunctionDoc('Demo\greet', 'greet', 'Demo', 'demo/pkg', 'src/fn.php', 1, 2, [], new TypeSignature(null, null), null, [], false);
        $document = new MarkdownDoc('demo/pkg', 'docs/guide.md', 'docs/guide.md', 'Guide');
        $manifest = new ComposerManifest($root, 'demo/pkg', '', ['Demo\\' => ['src']], [], [], [], []);
        $package = new DiscoveredPackage($manifest, false);
        $model = new ProjectModel('T', $root, [$package], new PackageGraph([]), [$widget], [$greet], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, ['demo\widget' => ['Domain']], null, [], [$document]);
        $services = (new SiteRenderer())->services($model);
        $signatures = new PageSignature();

        $digests = [
            $signatures->index($services),
            $signatures->package($services, $package, '# Demo'),
            $signatures->allItems($services, 'demo/pkg'),
            $signatures->layer($services, 'demo/pkg', 'Domain'),
            $signatures->namespaced($services, 'demo/pkg', 'Demo'),
            $signatures->classLike($services, $widget),
            $signatures->functionPage($services, $greet),
            $signatures->source($services, 'src/Widget.php', '<?php class Widget {}', null),
            $signatures->document($services, $document, '# Guide', null),
        ];

        self::assertCount(9, array_unique($digests));
    }

    public function testIndexFollowsTheWarningsTheSiteShows(): void
    {
        $renderer = new SiteRenderer();
        $quiet = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $warned = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, ['Something could not be documented.']);
        $signatures = new PageSignature();

        self::assertNotSame($signatures->index($renderer->services($quiet)), $signatures->index($renderer->services($warned)));
    }

    public function testAllItemsFollowsTheSymbolsOfItsPackage(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $renderer = new SiteRenderer();
        $empty = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $filled = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);

        self::assertNotSame(
            (new PageSignature())->allItems($renderer->services($empty), 'demo/pkg'),
            (new PageSignature())->allItems($renderer->services($filled), 'demo/pkg'),
        );
    }

    public function testLayerFollowsTheSymbolsAssignedToIt(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $renderer = new SiteRenderer();
        $unassigned = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $assigned = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, ['demo\widget' => ['Domain']], null, []);

        self::assertNotSame(
            (new PageSignature())->layer($renderer->services($unassigned), 'demo/pkg', 'Domain'),
            (new PageSignature())->layer($renderer->services($assigned), 'demo/pkg', 'Domain'),
        );
    }

    public function testNamespacedFollowsTheSymbolsOfOneNamespace(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $engine = new ClassLikeDoc('Demo\Engine', 'Engine', 'Demo', 'class', 'demo/pkg', 'src/Engine.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $renderer = new SiteRenderer();
        $alone = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $paired = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [$widget, $engine], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);

        self::assertNotSame(
            (new PageSignature())->namespaced($renderer->services($alone), 'demo/pkg', 'Demo'),
            (new PageSignature())->namespaced($renderer->services($paired), 'demo/pkg', 'Demo'),
        );
    }

    public function testFunctionPageFollowsWhatCallsTheFunction(): void
    {
        $greet = new FunctionDoc('Demo\greet', 'greet', 'Demo', 'demo/pkg', 'src/fn.php', 1, 2, [], new TypeSignature(null, null), null, [], false);
        $renderer = new SiteRenderer();
        $uncalled = new UsageIndex();
        $called = new UsageIndex();
        $called->build([new Usage('Demo\greet', null, 'function-call', 'Demo\Widget', 'run', 'src/Widget.php', 9, false)]);
        $quiet = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [], [$greet], new SymbolTable(), new HierarchyIndex(), $uncalled, new TestCaseIndex(), null, [], null, []);
        $loud = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [], [$greet], new SymbolTable(), new HierarchyIndex(), $called, new TestCaseIndex(), null, [], null, []);

        self::assertNotSame(
            (new PageSignature())->functionPage($renderer->services($quiet), $greet),
            (new PageSignature())->functionPage($renderer->services($loud), $greet),
        );
    }

    public function testPackageFollowsTheReadmeItShows(): void
    {
        $root = sys_get_temp_dir() . '/docgen-signature-' . bin2hex(random_bytes(4));
        $document = new MarkdownDoc('demo/pkg', 'docs/guide.md', 'docs/guide.md', 'Guide');
        $manifest = new ComposerManifest($root, 'demo/pkg', '', ['Demo\\' => ['src']], [], [], [], []);
        $package = new DiscoveredPackage($manifest, false);
        $model = new ProjectModel('T', $root, [$package], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$document]);
        $services = (new SiteRenderer())->services($model);
        $signatures = new PageSignature();

        self::assertNotSame($signatures->package($services, $package, '# Demo'), $signatures->package($services, $package, '# Demo edited'));
    }

    public function testDocumentFollowsTheProseItShows(): void
    {
        $root = sys_get_temp_dir() . '/docgen-signature-' . bin2hex(random_bytes(4));
        $document = new MarkdownDoc('demo/pkg', 'docs/guide.md', 'docs/guide.md', 'Guide');
        $model = new ProjectModel('T', $root, [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$document]);
        $services = (new SiteRenderer())->services($model);
        $signatures = new PageSignature();

        self::assertNotSame(
            $signatures->document($services, $document, '# Guide', null),
            $signatures->document($services, $document, '# Guide edited', null),
        );
    }

    public function testSourceFollowsTheFileItShows(): void
    {
        $root = sys_get_temp_dir() . '/docgen-signature-' . bin2hex(random_bytes(4));
        $model = new ProjectModel('T', $root, [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $signatures = new PageSignature();

        self::assertNotSame(
            $signatures->source($services, 'src/A.php', '<?php', null),
            $signatures->source($services, 'src/A.php', '<?php echo 1;', null),
        );
        self::assertNotSame(
            $signatures->source($services, 'src/A.php', '<?php', null),
            $signatures->source($services, 'src/A.php', '<?php', '<?php echo 2;'),
        );
    }

    public function testClassLikeFollowsTheFilesOfTheSymbolsItNames(): void
    {
        $root = sys_get_temp_dir() . '/docgen-signature-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Widget.php', '<?php class Widget {}');
        file_put_contents($root . '/src/Engine.php', '<?php class Engine {}');
        $engine = new ClassLikeDoc('Demo\Engine', 'Engine', 'Demo', 'class', 'demo/pkg', 'src/Engine.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, ['Demo\Engine'], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $table->registerClassLike($engine);
        $manifest = new ComposerManifest($root, 'demo/pkg', '', ['Demo\\' => ['src']], [], [], [], []);
        $model = new ProjectModel('T', $root, [new DiscoveredPackage($manifest, false)], new PackageGraph([]), [$widget, $engine], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();
        $before = (new PageSignature())->classLike($renderer->services($model), $widget);

        self::assertSame($before, (new PageSignature())->classLike($renderer->services($model), $widget));

        file_put_contents($root . '/src/Engine.php', '<?php class Engine { public $added; }');

        self::assertNotSame($before, (new PageSignature())->classLike($renderer->services($model), $widget));
    }

    public function testMemberPartsCollectWhatTheRestOfTheProjectSaysAboutMembers(): void
    {
        $root = sys_get_temp_dir() . '/docgen-signature-' . bin2hex(random_bytes(4));
        $method = new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('void', null), null, 6, 7);
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 5, 8, false, true, [], [], [], [], [], [$method], [], null, null, [], false);
        $coverage = new CoverageIndex();
        $coverage->addMethod('src/Widget.php', 6, new MethodCoverage(2, 2, 100.0));
        $model = new ProjectModel('T', $root, [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], $coverage, []);
        $services = (new SiteRenderer())->services($model);
        $signatures = new PageSignature();

        $parts = $signatures->memberParts($services, $widget);

        self::assertCount(1, $parts);
        self::assertSame('run', $parts[0][0]);
        self::assertInstanceOf(MethodCoverage::class, $signatures->coverageOf($services, 'src/Widget.php', 6, 7));
        self::assertNull($signatures->coverageOf($services, 'src/Widget.php', 30, 40));
    }

    public function testCoverageOfIsNothingWhenNoReportWasLoaded(): void
    {
        $model = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        self::assertNull((new PageSignature())->coverageOf($services, 'src/Widget.php', 1, 2));
    }
}
