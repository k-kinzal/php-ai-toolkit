<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page\Component;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\LineDiffer;
use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionLine;
use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
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
use PhpAiToolkit\DocGen\Render\Page\ClassLikePage;
use PhpAiToolkit\DocGen\Render\Page\Component\BreadcrumbHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\DocTextHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\ExampleHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\GraphSvg;
use PhpAiToolkit\DocGen\Render\Page\Component\MemberHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\RelationsHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\SignatureHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\SymbolListHtml;
use PhpAiToolkit\DocGen\Render\Page\Component\UsageListHtml;
use PhpAiToolkit\DocGen\Render\Page\DocumentPage;
use PhpAiToolkit\DocGen\Render\Page\FunctionPage;
use PhpAiToolkit\DocGen\Render\Page\IndexPage;
use PhpAiToolkit\DocGen\Render\Page\LayerPage;
use PhpAiToolkit\DocGen\Render\Page\NamespacePage;
use PhpAiToolkit\DocGen\Render\Page\PackagePage;
use PhpAiToolkit\DocGen\Render\Page\SourcePage;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\SearchIndexBuilder;
use PhpAiToolkit\DocGen\Render\Signature\PageSignature;
use PhpAiToolkit\DocGen\Render\Signature\SidebarDigest;
use PhpAiToolkit\DocGen\Render\SiteRenderer;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\Social\SocialCard;
use PhpAiToolkit\DocGen\Render\Social\SocialMeta;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Render\Page\Component\ExampleHtml
 * @uses \PhpAiToolkit\DocGen\Render\Page\AllItemsPage
 * @uses \PhpAiToolkit\DocGen\Analysis\Doctest\AssertionLine
 * @uses \PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \PhpAiToolkit\DocGen\Render\AssetPublisher
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\BreadcrumbHtml
 * @uses \PhpAiToolkit\DocGen\Render\Page\ClassLikePage
 * @uses \PhpAiToolkit\DocGen\Package\ComposerManifest
 * @uses \PhpAiToolkit\DocGen\Render\Diff\DiffHtml
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \PhpAiToolkit\DocGen\Package\DiscoveredPackage
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\DocTextHtml
 * @uses \PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \PhpAiToolkit\DocGen\Render\Page\DocumentPage
 * @uses \PhpAiToolkit\DocGen\Render\Page\FunctionPage
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\GraphSvg
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \PhpAiToolkit\DocGen\Render\HtmlText
 * @uses \PhpAiToolkit\DocGen\Render\Page\IndexPage
 * @uses \PhpAiToolkit\DocGen\Render\Page\LayerPage
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\LineDiffer
 * @uses \PhpAiToolkit\DocGen\Render\Diff\MarkdownDiffHtml
 * @uses \PhpAiToolkit\DocGen\Render\MarkdownInline
 * @uses \PhpAiToolkit\DocGen\Render\MarkdownRenderer
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\MemberHtml
 * @uses \PhpAiToolkit\DocGen\Render\Page\NamespacePage
 * @uses \PhpAiToolkit\DocGen\Package\PackageGraph
 * @uses \PhpAiToolkit\DocGen\Render\Page\PackagePage
 * @uses \PhpAiToolkit\DocGen\Render\PageChrome
 * @uses \PhpAiToolkit\DocGen\Render\Signature\PageSignature
 * @uses \PhpAiToolkit\DocGen\Render\PhpHighlighter
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml
 * @uses \PhpAiToolkit\DocGen\Analysis\ProjectModel
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\RelationsHtml
 * @uses \PhpAiToolkit\DocGen\Render\RenderKit
 * @uses \PhpAiToolkit\DocGen\Render\SearchIndexBuilder
 * @uses \PhpAiToolkit\DocGen\Render\Signature\SidebarDigest
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\SidebarHtml
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\SignatureHtml
 * @uses \PhpAiToolkit\DocGen\Filesystem\SiteFileWriter
 * @uses \PhpAiToolkit\DocGen\Render\SiteRenderer
 * @uses \PhpAiToolkit\DocGen\Render\SiteUrl
 * @uses \PhpAiToolkit\DocGen\Render\Social\SocialCard
 * @uses \PhpAiToolkit\DocGen\Render\Social\SocialMeta
 * @uses \PhpAiToolkit\DocGen\Render\Diff\SourceDiffHtml
 * @uses \PhpAiToolkit\DocGen\Render\Page\SourcePage
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\SymbolListHtml
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \PhpAiToolkit\DocGen\Render\TypeHtml
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \PhpAiToolkit\DocGen\Render\Page\Component\UsageListHtml
 * @uses \PhpAiToolkit\DocGen\Parallel\WorkScheduler
 * @uses \PhpAiToolkit\DocGen\Parallel\WorkerCount
 * @uses \PhpAiToolkit\DocGen\Parallel\WorkerPool
 */
#[CoversClass(ExampleHtml::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionLine::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentPage::class)]
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
#[UsesClass(SignatureHtml::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SourceDiffHtml::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UsageListHtml::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
final class ExampleHtmlTest extends TestCase
{
    public function testFigureRendersDoctestBadgeForRunnableExample(): void
    {
        $table = new SymbolTable();
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $html = (new ExampleHtml())->figure($services, 'Adding numbers', '$sum = 1; // => 1', true, 'Sum::of() example #1: Adding numbers');

        self::assertStringStartsWith('<figure class="example">', $html);
        self::assertStringContainsString('<span class="example-title">Adding numbers</span>', $html);
        self::assertStringContainsString('title="Runs as the doctest Sum::of() example #1: Adding numbers">doctest</span>', $html);
        self::assertStringContainsString('data-copy="vendor/bin/phpunit --filter &#039;/Sum\:\:of\(\) example \#1\: Adding numbers/&#039;"', $html);
        self::assertStringContainsString('<button class="copy-btn" type="button" title="Copy example">copy</button>', $html);
        self::assertStringEndsWith('</figure>' . "\n", $html);
    }

    public function testFigureOmitsTheRunButtonWhenNoIdentifierIsKnown(): void
    {
        $table = new SymbolTable();
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $html = (new ExampleHtml())->figure($services, 'Adding numbers', '$sum = 1; // => 1', true);

        self::assertStringContainsString('title="Executable as a doctest">doctest</span>', $html);
        self::assertStringNotContainsString('data-copy', $html);
    }

    public function testDoctestChipNamesTheExampleIdentifierWhenThereIsOne(): void
    {
        $table = new SymbolTable();
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $example = new ExampleHtml();

        self::assertStringContainsString('title="Executable as a doctest">', $example->doctestChip($services, ''));
        self::assertStringContainsString('title="Runs as the doctest Sum example #1">', $example->doctestChip($services, 'Sum example #1'));
    }

    public function testRunButtonCarriesTheCommandToCopy(): void
    {
        $table = new SymbolTable();
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $html = (new ExampleHtml())->runButton($services, 'Sum example #1');

        self::assertStringContainsString('data-copy="vendor/bin/phpunit --filter &#039;/Sum example \#1/&#039;"', $html);
        self::assertStringContainsString('>run</button>', $html);
    }

    public function testRunCommandQuotesTheExampleName(): void
    {
        self::assertSame(
            "vendor/bin/phpunit --filter '/Sum\:\:of\(\) example \#1/'",
            (new ExampleHtml())->runCommand('Sum::of() example #1'),
        );
    }

    public function testExampleNameMatchesTheNameDoctestGivesTheDataSet(): void
    {
        $example = new ExampleHtml();

        self::assertSame('Sum::of() example #1: Adding numbers', $example->exampleName('Sum::of()', 'Adding numbers', 0));
        self::assertSame('Sum example #3', $example->exampleName('Sum', null, 2));
    }

    public function testFigureOmitsBadgeForDisplayOnlyExample(): void
    {
        $table = new SymbolTable();
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $html = (new ExampleHtml())->figure($services, null, 'render();', false);

        self::assertStringContainsString('<span class="example-title">Example</span>', $html);
        self::assertStringNotContainsString('chip-doctest', $html);
    }

    public function testCodeBlockRendersMarkerSpansWithReconstructedText(): void
    {
        $table = new SymbolTable();
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $code = <<<'PHP'
$sum = add(1, 2); // => 3
echo $sum; // Output: 3
boom(); // throws RuntimeException: bad
    $x = add(1); // => 2
plain();
PHP;

        $html = (new ExampleHtml())->codeBlock($services, $code);

        self::assertStringStartsWith('<pre class="code-block doctest"><code>', $html);
        self::assertStringContainsString(' <span class="doct doct-return">// =&gt; 3</span>', $html);
        self::assertStringContainsString(' <span class="doct doct-output">// Output: 3</span>', $html);
        self::assertStringContainsString(' <span class="doct doct-throws">// throws RuntimeException: bad</span>', $html);
        self::assertStringContainsString("\n" . '    <span class="tok-var">$x</span>', $html);
        self::assertSame(4, substr_count($html, '<span class="doct doct-'));
        self::assertStringEndsWith('</code></pre>', $html);
    }

    public function testMarkerTextRebuildsMarkerComments(): void
    {
        self::assertSame('// => 3', (new ExampleHtml())->markerText('return', '3', null));
        self::assertSame('// Output: 3', (new ExampleHtml())->markerText('output', '3', null));
        self::assertSame('// throws RuntimeException: bad', (new ExampleHtml())->markerText('throws', 'RuntimeException', 'bad'));
        self::assertSame('// throws RuntimeException', (new ExampleHtml())->markerText('throws', 'RuntimeException', null));
        self::assertSame('// throws RuntimeException', (new ExampleHtml())->markerText('throws', 'RuntimeException', ''));
    }
}
