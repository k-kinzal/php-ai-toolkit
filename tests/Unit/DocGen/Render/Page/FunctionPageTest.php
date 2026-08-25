<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Diff\LineDiffer;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Analysis\Doctest\AssertionScanner;
use Toolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\DocTag;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;
use Toolkit\DocGen\Analysis\Model\ParameterDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\Parse\AstParser;
use Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder;
use Toolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use Toolkit\DocGen\Analysis\Parse\FileSymbolCollector;
use Toolkit\DocGen\Analysis\Parse\FileSymbols;
use Toolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use Toolkit\DocGen\Analysis\Parse\ParameterModifiers;
use Toolkit\DocGen\Analysis\Parse\PhpParserBridge;
use Toolkit\DocGen\Analysis\Parse\SymbolContext;
use Toolkit\DocGen\Analysis\Parse\UseMapCollector;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\Usage;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Filesystem\SiteFileWriter;
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
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\Page\AllItemsPage;
use Toolkit\DocGen\Render\Page\ClassLikePage;
use Toolkit\DocGen\Render\Page\Component\BreadcrumbHtml;
use Toolkit\DocGen\Render\Page\Component\DocTextHtml;
use Toolkit\DocGen\Render\Page\Component\DocumentListHtml;
use Toolkit\DocGen\Render\Page\Component\ExampleHtml;
use Toolkit\DocGen\Render\Page\Component\GraphSvg;
use Toolkit\DocGen\Render\Page\Component\MemberHtml;
use Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml;
use Toolkit\DocGen\Render\Page\Component\RelationsHtml;
use Toolkit\DocGen\Render\Page\Component\SidebarHtml;
use Toolkit\DocGen\Render\Page\Component\SignatureHtml;
use Toolkit\DocGen\Render\Page\Component\SymbolDescription;
use Toolkit\DocGen\Render\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Render\Page\Component\SymbolRow;
use Toolkit\DocGen\Render\Page\Component\TestCaseHtml;
use Toolkit\DocGen\Render\Page\Component\UsageListHtml;
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
use Toolkit\DocGen\Render\TypeRenderContext;

/**
 * @covers \Toolkit\DocGen\Render\Page\FunctionPage
 * @uses \Toolkit\DocGen\Render\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\AssetPublisher
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Render\Page\Component\BreadcrumbHtml
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Render\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Render\Diff\DiffBanner
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Render\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Analysis\Model\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Analysis\Model\DocTag
 * @uses \Toolkit\DocGen\Render\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Render\Page\Component\DocumentListHtml
 * @uses \Toolkit\DocGen\Render\Page\DocumentPage
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Render\Page\Component\ExampleHtml
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\FunctionDoc
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
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Render\Page\NamespacePage
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Render\Page\PackagePage
 * @uses \Toolkit\DocGen\Render\PageChrome
 * @uses \Toolkit\DocGen\Render\Signature\PageSignature
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Render\PhpHighlighter
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Render\Page\Component\RelationsHtml
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\RepositoryLink
 * @uses \Toolkit\DocGen\Render\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Render\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Render\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Render\Page\SidebarScope
 * @uses \Toolkit\DocGen\Render\Page\Component\SignatureHtml
 * @uses \Toolkit\DocGen\Filesystem\SiteFileWriter
 * @uses \Toolkit\DocGen\Render\SiteRenderer
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Render\Social\SocialCard
 * @uses \Toolkit\DocGen\Render\Social\SocialMeta
 * @uses \Toolkit\DocGen\Render\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Render\Page\SourcePage
 * @uses \Toolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolDescription
 * @uses \Toolkit\DocGen\Render\Page\SymbolIndex
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolRow
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Render\Page\Component\TestCaseHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Render\TypeRenderContext
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\Usage
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Render\Page\Component\UsageListHtml
 * @uses \Toolkit\DocGen\Analysis\Parse\UseMapCollector
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 */
#[CoversClass(FunctionPage::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(DiffBanner::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffModeControl::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocTag::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentListHtml::class)]
#[UsesClass(DocumentPage::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(ExampleHtml::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(FunctionDoc::class)]
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
#[UsesClass(RelationsHtml::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(RepositoryLink::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarDigest::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SignatureHtml::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SourceDiffHtml::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolDescription::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseHtml::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeRenderContext::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(Usage::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UsageListHtml::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
final class FunctionPageTest extends TestCase
{
    public function testRenderProducesCompleteDocument(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * Makes a widget count.
 */
function make(int $count): int
{
    return $count;
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/functions.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/functions.php', false);
        $function = $symbols->functions[0];
        $table = new SymbolTable();
        $table->registerFunction($function);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $html = (new FunctionPage())->render($services, $function);

        self::assertStringStartsWith('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<title>make — Demo Docs</title>', $html);
        self::assertStringContainsString('<h1><span class="chip chip-kind k-function">function</span>make</h1>', $html);
        self::assertStringContainsString(
            '<a href="../../../demo/pkg/index.html">demo/pkg</a><span class="crumb-sep">::</span>'
            . '<a href="../../../demo/pkg/Demo/index.html">Demo</a><span class="crumb-sep">::</span>'
            . '<span class="crumb-current">make()</span>',
            $html,
        );
        self::assertStringContainsString('<p class="lede">Makes a widget count.</p>', $html);
    }

    public function testContentRendersSignatureDocsParamsThrowsAndCallers(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * Makes a widget count.
 *
 * @param int $count the starting count
 *
 * @throws \RuntimeException when the count is negative
 */
function make(int $count): int
{
    return $count;
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/functions.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/functions.php', false);
        $function = $symbols->functions[0];
        $table = new SymbolTable();
        $table->registerFunction($function);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([new Usage('Demo\make', null, 'function-call', 'Demo\Caller', 'boot', 'src/Demo/App.php', 8, false)]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $pagePath = 'demo/pkg/Demo/function.make.html';
        $context = new TypeRenderContext($pagePath, 'Demo', [], [], [], $table);

        $html = (new FunctionPage())->content($services, $pagePath, $function, $context);

        self::assertStringContainsString(
            '<a class="src-link" href="../../../src/src/Demo/functions.php.html#L' . $function->startLine . '">src/Demo/functions.php:' . $function->startLine . '</a>',
            $html,
        );
        self::assertStringContainsString('<span class="t-key">function</span> <span class="sig-name">make</span>', $html);
        self::assertStringContainsString('<p class="lede">Makes a widget count.</p>', $html);
        self::assertStringContainsString('<div class="member-block"><h4>Parameters</h4>', $html);
        self::assertStringContainsString('<code class="t-var">$count</code>', $html);
        self::assertStringContainsString('the starting count', $html);
        self::assertStringContainsString(
            '<div class="member-block"><h4>Returns</h4><div class="type-row"><code><span class="t-key">int</span></code></div></div>',
            $html,
        );
        self::assertStringContainsString(
            '<div class="member-block"><h4>Throws</h4><div class="type-row">'
            . '<code><span class="t-ext" title="RuntimeException">RuntimeException</span></code>'
            . ' <span class="type-note">when the count is negative</span></div></div>',
            $html,
        );
        self::assertStringContainsString('Called from <span class="count">1</span>', $html);
        self::assertStringContainsString('src/Demo/App.php:8', $html);
    }
}
