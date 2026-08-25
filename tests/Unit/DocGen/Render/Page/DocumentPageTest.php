<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffIndex;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Diff\LcsMatcher;
use Toolkit\DocGen\Analysis\Diff\LineDiffer;
use Toolkit\DocGen\Analysis\Doctest\AssertionScanner;
use Toolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use Toolkit\DocGen\Analysis\Model\MarkdownDoc;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Package\ComposerManifest;
use Toolkit\DocGen\Package\DiscoveredPackage;
use Toolkit\DocGen\Package\PackageGraph;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkerPool;
use Toolkit\DocGen\Parallel\WorkScheduler;
use Toolkit\DocGen\Render\AssetPublisher;
use Toolkit\DocGen\Render\Diff\DiffBanner;
use Toolkit\DocGen\Render\Diff\DiffHtml;
use Toolkit\DocGen\Render\Diff\DiffModeControl;
use Toolkit\DocGen\Render\Diff\MarkdownDiffHtml;
use Toolkit\DocGen\Render\Diff\SourceDiffHtml;
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\MarkdownInline;
use Toolkit\DocGen\Render\MarkdownLinks;
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\Page\AllItemsPage;
use Toolkit\DocGen\Render\Page\ClassLikePage;
use Toolkit\DocGen\Render\Page\Component\BreadcrumbHtml;
use Toolkit\DocGen\Render\Page\Component\DocTextHtml;
use Toolkit\DocGen\Render\Page\Component\DocumentListHtml;
use Toolkit\DocGen\Render\Page\Component\GraphSvg;
use Toolkit\DocGen\Render\Page\Component\MemberHtml;
use Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml;
use Toolkit\DocGen\Render\Page\Component\RelationsHtml;
use Toolkit\DocGen\Render\Page\Component\SidebarHtml;
use Toolkit\DocGen\Render\Page\Component\SymbolListHtml;
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
use Toolkit\DocGen\Render\RepositoryLink;
use Toolkit\DocGen\Render\SearchIndexBuilder;
use Toolkit\DocGen\Render\Signature\PageSignature;
use Toolkit\DocGen\Render\Signature\SidebarDigest;
use Toolkit\DocGen\Render\SiteRenderer;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\Social\SocialCard;
use Toolkit\DocGen\Render\Social\SocialMeta;
use Toolkit\DocGen\Render\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Render\Page\DocumentPage
 * @uses \Toolkit\DocGen\Render\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\AssetPublisher
 * @uses \Toolkit\DocGen\Render\Page\Component\BreadcrumbHtml
 * @uses \Toolkit\DocGen\Render\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Render\Diff\DiffBanner
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Render\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Render\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Render\Page\Component\DocumentListHtml
 * @uses \Toolkit\DocGen\Render\Page\FunctionPage
 * @uses \Toolkit\DocGen\Render\Page\Component\GraphSvg
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Render\Page\IndexPage
 * @uses \Toolkit\DocGen\Render\Page\LayerPage
 * @uses \Toolkit\DocGen\Analysis\Diff\LcsMatcher
 * @uses \Toolkit\DocGen\Analysis\Diff\LineDiffer
 * @uses \Toolkit\DocGen\Render\Diff\MarkdownDiffHtml
 * @uses \Toolkit\DocGen\Analysis\Model\MarkdownDoc
 * @uses \Toolkit\DocGen\Render\MarkdownInline
 * @uses \Toolkit\DocGen\Render\MarkdownLinks
 * @uses \Toolkit\DocGen\Render\MarkdownRenderer
 * @uses \Toolkit\DocGen\Render\Page\Component\MemberHtml
 * @uses \Toolkit\DocGen\Render\Page\NamespacePage
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Render\Page\PackagePage
 * @uses \Toolkit\DocGen\Render\PageChrome
 * @uses \Toolkit\DocGen\Render\Signature\PageSignature
 * @uses \Toolkit\DocGen\Render\PhpHighlighter
 * @uses \Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Render\Page\Component\RelationsHtml
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\RepositoryLink
 * @uses \Toolkit\DocGen\Render\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Render\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Render\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Render\Page\SidebarScope
 * @uses \Toolkit\DocGen\Render\SiteRenderer
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Render\Social\SocialCard
 * @uses \Toolkit\DocGen\Render\Social\SocialMeta
 * @uses \Toolkit\DocGen\Render\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Render\Page\SourcePage
 * @uses \Toolkit\DocGen\Render\Page\SymbolIndex
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 */
#[CoversClass(DocumentPage::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DiffBanner::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffModeControl::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentListHtml::class)]
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
#[UsesClass(NamespacePage::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PackagePage::class)]
#[UsesClass(PageChrome::class)]
#[UsesClass(PageSignature::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(PrivateSurfaceHtml::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RelationsHtml::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(RepositoryLink::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarDigest::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SourceDiffHtml::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
final class DocumentPageTest extends TestCase
{
    public function testRenderProducesCompleteDocumentWithCrumbAndTitle(): void
    {
        $guide = new MarkdownDoc('demo/pkg', 'docs/guide.md', 'docs/guide.md', 'Guide');
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$guide]);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new DocumentPage())->render($services, $guide, "# Guide\n\nIntro text.");

        self::assertStringStartsWith('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<title>Guide — Demo Docs</title>', $html);
        self::assertStringContainsString(
            '<a href="../../../../demo/pkg/index.html">demo/pkg</a><span class="crumb-sep">::</span><span class="crumb-current">docs/guide.md</span>',
            $html,
        );
        self::assertStringContainsString('<h1><span class="chip chip-kind k-document">document</span>Guide</h1>', $html);
        self::assertStringContainsString('<h2>Guide</h2>', $html);
    }

    public function testContentHeadsWithTheTitleAndPath(): void
    {
        $guide = new MarkdownDoc('demo/pkg', 'docs/guide.md', 'docs/guide.md', 'Guide');
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$guide]);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new DocumentPage())->content($services, 'demo/pkg/doc/docs/guide.md.html', $guide, 'Intro text.');

        self::assertStringStartsWith(
            '<div class="symbol-head"><h1><span class="chip chip-kind k-document">document</span>Guide</h1>'
            . '<div class="symbol-meta"><span class="src-link">docs/guide.md</span></div></div>' . "\n"
            . '<section class="readme"><p>Intro text.</p>',
            $html,
        );
        self::assertStringEndsWith("</section>\n", $html);
    }

    public function testBodyResolvesSiblingDocumentLinksAndHighlightsPhp(): void
    {
        $guide = new MarkdownDoc('demo/pkg', 'docs/guide.md', 'docs/guide.md', 'Guide');
        $rule = new MarkdownDoc('demo/pkg', 'docs/rules/Rule.md', 'docs/rules/Rule.md', 'Rule');
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$guide, $rule]);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $markdown = "See [the rule](rules/Rule.md) and [the tree](tree.yaml).\n\n```php\n<?php echo 1;\n```";

        $html = (new DocumentPage())->body($services, 'demo/pkg/doc/docs/guide.md.html', $guide, $markdown);

        self::assertStringContainsString('<a href="../../../../demo/pkg/doc/docs/rules/Rule.md.html">the rule</a>', $html);
        self::assertStringContainsString('<span class="md-target" title="tree.yaml">the tree</span>', $html);
        self::assertStringContainsString('<pre class="code-block"><code>&lt;?<span class="tok-id">php</span> <span class="tok-kw">echo</span>', $html);
    }

    public function testBodyMarksTheChangedBlocksOfAComparedDocument(): void
    {
        $guide = new MarkdownDoc('demo/pkg', 'docs/guide.md', 'docs/guide.md', 'Guide');
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$guide]);
        $services = (new SiteRenderer())->services($model, new DiffIndex('main', 'HEAD'));

        $html = (new DocumentPage())->body($services, 'demo/pkg/doc/docs/guide.md.html', $guide, "Intro.\n\nNew part.\n", "Intro.\n");

        self::assertStringContainsString('<div class="doc-block" data-diff="same"><p>Intro.</p>', $html);
        self::assertStringContainsString('<div class="doc-block" data-diff="added"><p>New part.</p>', $html);
    }

    public function testFenceHighlightsPhpAndLeavesOtherLanguagesToTheRenderer(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $fence = (new DocumentPage())->fence($services);

        self::assertNull($fence('echo 1;', 'bash'));
        self::assertStringContainsString('<pre class="code-block"><code><span class="tok-kw">echo</span>', (string) $fence('echo 1;', 'php'));
    }
}
