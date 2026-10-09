<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report\Signature;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Action\GenerateDocumentation;
use Toolkit\DocGen\Action\GenerationRequest;
use Toolkit\DocGen\Action\GenerationResult;
use Toolkit\DocGen\Analysis\AnalysisOptions;
use Toolkit\DocGen\Analysis\Coverage\CoverageIndex;
use Toolkit\DocGen\Analysis\Coverage\MethodCoverage;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Cache\ToolkitFingerprint;
use Toolkit\DocGen\Compare\DiffKey;
use Toolkit\DocGen\Compare\DiffStatus;
use Toolkit\DocGen\Compare\LineDiffer;
use Toolkit\DocGen\Discovery\MarkdownDoc;
use Toolkit\DocGen\Discovery\Package\ComposerManifest;
use Toolkit\DocGen\Discovery\Package\DiscoveredPackage;
use Toolkit\DocGen\Discovery\SourceDiscovery;
use Toolkit\DocGen\Discovery\SourceFile;
use Toolkit\DocGen\Discovery\SourceSelection;
use Toolkit\DocGen\Discovery\SourceSet;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkerPool;
use Toolkit\DocGen\Parallel\WorkScheduler;
use Toolkit\DocGen\Parse\ParsedProject;
use Toolkit\DocGen\Parse\Reference\Usage;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\FunctionDoc;
use Toolkit\DocGen\Parse\Symbol\MethodDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;
use Toolkit\DocGen\Report\AssetPublisher;
use Toolkit\DocGen\Report\Diff\DiffHtml;
use Toolkit\DocGen\Report\Diff\MarkdownDiffHtml;
use Toolkit\DocGen\Report\Diff\SourceDiffHtml;
use Toolkit\DocGen\Report\Doctest\AssertionScanner;
use Toolkit\DocGen\Report\Doctest\DoctestExtractor;
use Toolkit\DocGen\Report\HtmlText;
use Toolkit\DocGen\Report\MarkdownInline;
use Toolkit\DocGen\Report\MarkdownRenderer;
use Toolkit\DocGen\Report\Page\AllItemsPage;
use Toolkit\DocGen\Report\Page\ClassLikePage;
use Toolkit\DocGen\Report\Page\Component\DocTextHtml;
use Toolkit\DocGen\Report\Page\Component\DocumentListHtml;
use Toolkit\DocGen\Report\Page\Component\GraphSvg;
use Toolkit\DocGen\Report\Page\Component\MemberHtml;
use Toolkit\DocGen\Report\Page\Component\PrivateSurfaceHtml;
use Toolkit\DocGen\Report\Page\Component\RelationsHtml;
use Toolkit\DocGen\Report\Page\Component\SidebarHtml;
use Toolkit\DocGen\Report\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Report\Page\Component\SymbolRow;
use Toolkit\DocGen\Report\Page\DocumentPage;
use Toolkit\DocGen\Report\Page\FunctionPage;
use Toolkit\DocGen\Report\Page\IndexPage;
use Toolkit\DocGen\Report\Page\LayerPage;
use Toolkit\DocGen\Report\Page\NamespacePage;
use Toolkit\DocGen\Report\Page\PackagePage;
use Toolkit\DocGen\Report\Page\SidebarScope;
use Toolkit\DocGen\Report\Page\SourcePage;
use Toolkit\DocGen\Report\Page\SymbolIndex;
use Toolkit\DocGen\Report\PageChrome;
use Toolkit\DocGen\Report\PhpHighlighter;
use Toolkit\DocGen\Report\RenderedSite;
use Toolkit\DocGen\Report\RenderKit;
use Toolkit\DocGen\Report\SearchIndexBuilder;
use Toolkit\DocGen\Report\Signature\PageSignature;
use Toolkit\DocGen\Report\Signature\SidebarDigest;
use Toolkit\DocGen\Report\Signature\SourceDigestIndex;
use Toolkit\DocGen\Report\Signature\SymbolReferenceScanner;
use Toolkit\DocGen\Report\SiteRenderer;
use Toolkit\DocGen\Report\SiteUrl;
use Toolkit\DocGen\Report\Social\SocialCard;
use Toolkit\DocGen\Report\Social\SocialMeta;
use Toolkit\DocGen\Report\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Report\Signature\PageSignature
 * @uses \Toolkit\DocGen\Report\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Report\AssetPublisher
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Report\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Analysis\Coverage\CoverageIndex
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Report\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Report\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Report\Page\Component\DocumentListHtml
 * @uses \Toolkit\DocGen\Report\Page\DocumentPage
 * @uses \Toolkit\DocGen\Parse\Symbol\FunctionDoc
 * @uses \Toolkit\DocGen\Report\Page\FunctionPage
 * @uses \Toolkit\DocGen\Report\Page\Component\GraphSvg
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Report\HtmlText
 * @uses \Toolkit\DocGen\Report\Page\IndexPage
 * @uses \Toolkit\DocGen\Report\Page\LayerPage
 * @uses \Toolkit\DocGen\Compare\LineDiffer
 * @uses \Toolkit\DocGen\Report\Diff\MarkdownDiffHtml
 * @uses \Toolkit\DocGen\Discovery\MarkdownDoc
 * @uses \Toolkit\DocGen\Report\MarkdownInline
 * @uses \Toolkit\DocGen\Report\MarkdownRenderer
 * @uses \Toolkit\DocGen\Report\Page\Component\MemberHtml
 * @uses \Toolkit\DocGen\Analysis\Coverage\MethodCoverage
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Report\Page\NamespacePage
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Report\Page\PackagePage
 * @uses \Toolkit\DocGen\Report\PageChrome
 * @uses \Toolkit\DocGen\Report\PhpHighlighter
 * @uses \Toolkit\DocGen\Report\Page\Component\PrivateSurfaceHtml
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Report\Page\Component\RelationsHtml
 * @uses \Toolkit\DocGen\Report\RenderKit
 * @uses \Toolkit\DocGen\Report\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Report\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Report\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Report\Page\SidebarScope
 * @uses \Toolkit\DocGen\Report\SiteRenderer
 * @uses \Toolkit\DocGen\Report\SiteUrl
 * @uses \Toolkit\DocGen\Report\Social\SocialCard
 * @uses \Toolkit\DocGen\Report\Social\SocialMeta
 * @uses \Toolkit\DocGen\Report\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Report\Signature\SourceDigestIndex
 * @uses \Toolkit\DocGen\Report\Page\SourcePage
 * @uses \Toolkit\DocGen\Report\Page\SymbolIndex
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Report\Signature\SymbolReferenceScanner
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolRow
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Cache\ToolkitFingerprint
 * @uses \Toolkit\DocGen\Report\TypeHtml
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Parse\Reference\Usage
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 * @uses \Toolkit\DocGen\Discovery\SourceSelection
 * @uses \Toolkit\DocGen\Discovery\SourceFile
 * @uses \Toolkit\DocGen\Discovery\SourceSet
 * @uses \Toolkit\DocGen\Discovery\SourceDiscovery
 * @uses \Toolkit\DocGen\Parse\ParsedProject
 * @uses \Toolkit\DocGen\Analysis\AnalysisOptions
 * @uses \Toolkit\DocGen\Analysis\ProjectAnalyzer
 * @uses \Toolkit\DocGen\Action\GenerateDocumentation
 * @uses \Toolkit\DocGen\Action\GenerationRequest
 * @uses \Toolkit\DocGen\Action\GenerationResult
 * @uses \Toolkit\DocGen\Report\RenderedSite
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
#[UsesClass(SourceSelection::class)]
#[UsesClass(SourceFile::class)]
#[UsesClass(SourceSet::class)]
#[UsesClass(SourceDiscovery::class)]
#[UsesClass(ParsedProject::class)]
#[UsesClass(AnalysisOptions::class)]
#[UsesClass(ProjectAnalyzer::class)]
#[UsesClass(GenerateDocumentation::class)]
#[UsesClass(GenerationRequest::class)]
#[UsesClass(GenerationResult::class)]
#[UsesClass(RenderedSite::class)]
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
