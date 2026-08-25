<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Signature;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\LineDiffer;
use Toolkit\DocGen\Analysis\Doctest\AssertionScanner;
use Toolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
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
use Toolkit\DocGen\Render\Page\SourcePage;
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
 * @covers \Toolkit\DocGen\Render\Signature\SymbolReferenceScanner
 * @uses \Toolkit\DocGen\Render\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\AssetPublisher
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Render\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Render\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
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
 * @uses \Toolkit\DocGen\Render\MarkdownInline
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
 * @uses \Toolkit\DocGen\Render\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Render\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Render\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Render\SiteRenderer
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Render\Social\SocialCard
 * @uses \Toolkit\DocGen\Render\Social\SocialMeta
 * @uses \Toolkit\DocGen\Render\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Render\Signature\SourceDigestIndex
 * @uses \Toolkit\DocGen\Render\Page\SourcePage
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 */
#[CoversClass(SymbolReferenceScanner::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
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
#[UsesClass(MarkdownInline::class)]
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
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarDigest::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SourceDiffHtml::class)]
#[UsesClass(SourceDigestIndex::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
final class SymbolReferenceScannerTest extends TestCase
{
    public function testNamesListsEveryReadingOfEveryWrittenName(): void
    {
        $names = (new SymbolReferenceScanner())->names('Widget and Other\Thing', 'Demo', ['other' => 'Vendor\Other']);

        self::assertSame([
            'Widget',
            'and',
            'Other\Thing',
            'Demo\Widget',
            'Demo\and',
            'Demo\Other\Thing',
            'Vendor\Other\Thing',
        ], $names);
    }

    public function testNamesReadsEveryNameOutOfSerializedData(): void
    {
        $scanner = new SymbolReferenceScanner();

        self::assertContains('Demo\Widget', $scanner->names(serialize(new TypeSignature('Demo\Widget', null)), '', []));
        self::assertSame([], $scanner->names('', '', []));
    }

    public function testImportedResolvesAWrittenNameThroughTheImportsOfItsFile(): void
    {
        $scanner = new SymbolReferenceScanner();

        self::assertSame('Vendor\Other', $scanner->imported('Other', ['other' => 'Vendor\Other']));
        self::assertSame('Vendor\Other\Deep', $scanner->imported('Other\Deep', ['other' => 'Vendor\Other']));
        self::assertNull($scanner->imported('Widget', ['other' => 'Vendor\Other']));
    }

    public function testSymbolDigestNamesWhatEachNameResolvesTo(): void
    {
        $root = sys_get_temp_dir() . '/docgen-scanner-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Widget.php', '<?php class Widget {}');
        file_put_contents($root . '/src/fn.php', '<?php function greet() {}');
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $greet = new FunctionDoc('Demo\greet', 'greet', 'Demo', 'demo/pkg', 'src/fn.php', 1, 2, [], new TypeSignature(null, null), null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $table->registerFunction($greet);
        $model = new ProjectModel('T', $root, [], new PackageGraph([]), [$widget], [$greet], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $scanner = new SymbolReferenceScanner();
        $sources = new SourceDigestIndex();

        self::assertStringStartsWith('class-like|', $scanner->symbolDigest($services, $sources, 'Demo\Widget'));
        self::assertStringEndsWith('|demo/pkg|src', $scanner->symbolDigest($services, $sources, 'Demo\Widget'));
        self::assertStringStartsWith('function|', $scanner->symbolDigest($services, $sources, 'Demo\greet'));
        self::assertSame(SymbolReferenceScanner::UNRESOLVED, $scanner->symbolDigest($services, $sources, 'Demo\Absent'));
    }

    public function testResolvedReportsANameThatIsNoDocumentedSymbol(): void
    {
        $model = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        self::assertSame(
            SymbolReferenceScanner::UNRESOLVED,
            (new SymbolReferenceScanner())->resolved($services, new SourceDigestIndex(), 'Demo\\Widget'),
        );
    }

    public function testDigestFollowsTheFileOfEveryNamedSymbol(): void
    {
        $root = sys_get_temp_dir() . '/docgen-scanner-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Widget.php', '<?php class Widget {}');
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $model = new ProjectModel('T', $root, [], new PackageGraph([]), [$widget], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $before = (new SymbolReferenceScanner())->digest($services, new SourceDigestIndex(), 'Demo\Widget is named here', '', []);

        self::assertSame($before, (new SymbolReferenceScanner())->digest($services, new SourceDigestIndex(), 'Demo\Widget is named here', '', []));

        file_put_contents($root . '/src/Widget.php', '<?php class Widget { public $added; }');

        self::assertNotSame($before, (new SymbolReferenceScanner())->digest($services, new SourceDigestIndex(), 'Demo\Widget is named here', '', []));
    }

    public function testDigestNoticesASymbolThatIsNotThereYet(): void
    {
        $root = sys_get_temp_dir() . '/docgen-scanner-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Widget.php', '<?php class Widget {}');
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $empty = new SymbolTable();
        $filled = new SymbolTable();
        $filled->registerClassLike($widget);
        $without = new ProjectModel('T', $root, [], new PackageGraph([]), [], [], $empty, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $with = new ProjectModel('T', $root, [], new PackageGraph([]), [$widget], [], $filled, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();

        self::assertNotSame(
            (new SymbolReferenceScanner())->digest($renderer->services($without), new SourceDigestIndex(), 'Demo\Widget', '', []),
            (new SymbolReferenceScanner())->digest($renderer->services($with), new SourceDigestIndex(), 'Demo\Widget', '', []),
        );
    }
}
