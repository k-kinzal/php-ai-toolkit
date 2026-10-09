<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report\Page\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Compare\DiffKey;
use Toolkit\DocGen\Compare\DiffStatus;
use Toolkit\DocGen\Parse\Reference\Usage;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Report\Diff\DiffHtml;
use Toolkit\DocGen\Report\Doctest\AssertionScanner;
use Toolkit\DocGen\Report\Doctest\DoctestExtractor;
use Toolkit\DocGen\Report\HtmlText;
use Toolkit\DocGen\Report\MarkdownInline;
use Toolkit\DocGen\Report\MarkdownRenderer;
use Toolkit\DocGen\Report\Page\Component\UsageListHtml;
use Toolkit\DocGen\Report\PhpHighlighter;
use Toolkit\DocGen\Report\RenderKit;
use Toolkit\DocGen\Report\SiteUrl;
use Toolkit\DocGen\Report\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Report\Page\Component\UsageListHtml
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Report\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Report\HtmlText
 * @uses \Toolkit\DocGen\Report\MarkdownInline
 * @uses \Toolkit\DocGen\Report\MarkdownRenderer
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Report\RenderKit
 * @uses \Toolkit\DocGen\Report\SiteUrl
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Report\TypeHtml
 * @uses \Toolkit\DocGen\Parse\Reference\Usage
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
#[CoversClass(UsageListHtml::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(Usage::class)]
#[UsesClass(UsageIndex::class)]
final class UsageListHtmlTest extends TestCase
{
    public function testBuildWrapsEveryUsageInOneListItem(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $first = new Usage('Demo\Widget', null, 'new', null, null, 'src/Demo/A.php', 3, false);
        $second = new Usage('Demo\Widget', null, 'type', null, null, 'src/Demo/B.php', 9, false);

        $html = (new UsageListHtml())->build($services, 'index.html', [$first, $second]);

        self::assertStringStartsWith('<ul class="usage-list"><li>', $html);
        self::assertStringEndsWith('</li></ul>', $html);
        self::assertSame(2, substr_count($html, '<li>'));
        self::assertStringContainsString('src/Demo/A.php:3', $html);
        self::assertStringContainsString('src/Demo/B.php:9', $html);
        self::assertSame('<ul class="usage-list"></ul>', (new UsageListHtml())->build($services, 'index.html', []));
    }

    public function testItemLinksTheOriginSymbolToItsPageAndTheLocationToTheSource(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $caller = new ClassLikeDoc('Demo\Caller', 'Caller', 'Demo', 'class', 'demo/pkg', 'src/Demo/Caller.php', 3, 12, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $table->registerClassLike($caller);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$widget, $caller], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $usage = new Usage('Demo\Widget', null, 'new', 'Demo\Caller', 'boot', 'src/Demo/Caller.php', 21, false);

        self::assertSame(
            '<span class="usage-kind">new</span>'
            . ' <a href="../../../demo/pkg/Demo/class.Caller.html" title="Demo\Caller">Demo\Caller::boot()</a>'
            . ' <a class="usage-loc" href="../../../src/src/Demo/Caller.php.html#L21">src/Demo/Caller.php:21</a>',
            (new UsageListHtml())->item($services, 'demo/pkg/Demo/class.Widget.html', $usage),
        );
    }

    public function testItemNamesTheFileWhenTheReferenceHasNoOriginClass(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $usage = new Usage('Demo\Widget', null, 'type', null, null, 'src/bootstrap.php', 5, false);

        self::assertSame(
            '<span class="usage-kind">type</span> src/bootstrap.php'
            . ' <a class="usage-loc" href="src/src/bootstrap.php.html#L5">src/bootstrap.php:5</a>',
            (new UsageListHtml())->item($services, 'index.html', $usage),
        );
    }

    public function testCallItemNamesTheCalledSymbolByShortNameAndLinksTheCallLine(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $caller = new ClassLikeDoc('Demo\Caller', 'Caller', 'Demo', 'class', 'demo/pkg', 'src/Demo/Caller.php', 3, 12, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $table->registerClassLike($caller);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$widget, $caller], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $usage = new Usage('Demo\Caller', 'boot', 'method-call', 'Demo\Widget', 'run', 'src/Demo/Widget.php', 30, false);

        self::assertSame(
            '<span class="usage-kind">method-call</span>'
            . ' <a href="../../../demo/pkg/Demo/class.Caller.html" title="Demo\Caller">Caller::boot()</a>'
            . ' <a class="usage-loc" href="../../../src/src/Demo/Widget.php.html#L30">line 30</a>',
            (new UsageListHtml())->callItem($services, 'demo/pkg/Demo/class.Widget.html', $usage),
        );
    }

    public function testSymbolLinkSendsDevSymbolsToTheirSourcePageWithATestChip(): void
    {
        $probe = new ClassLikeDoc('Tests\WidgetTest', 'WidgetTest', 'Tests', 'class', 'demo/pkg', 'tests/WidgetTest.php', 7, 30, false, true, [], [], [], [], [], [], [], null, null, [], true);
        $table = new SymbolTable();
        $table->registerClassLike($probe);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$probe], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            '<a href="../../../src/tests/WidgetTest.php.html#L7" title="Tests\WidgetTest">Tests\WidgetTest::testRun()</a>'
            . ' <span class="chip chip-sm chip-test">test</span>',
            (new UsageListHtml())->symbolLink($services, 'demo/pkg/Demo/class.Widget.html', 'Tests\WidgetTest', 'Tests\WidgetTest::testRun()', 'tests/WidgetTest.php', 15),
        );
    }

    public function testSymbolLinkRendersPlainCodeForSymbolsOutsideTheModel(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            '<code>Demo\Unknown</code>',
            (new UsageListHtml())->symbolLink($services, 'index.html', 'Demo\Unknown', 'Demo\Unknown', 'src/x.php', 3),
        );
    }

    public function testSectionCountsUsagesWithoutAnySeparateTestTally(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $list = [
            new Usage('Demo\Widget', null, 'new', null, null, 'src/Demo/A.php', 3, false),
            new Usage('Demo\Widget', null, 'type', null, null, 'src/Demo/B.php', 9, false),
        ];

        $html = (new UsageListHtml())->section($services, 'index.html', 'Called from', $list, false);

        self::assertStringStartsWith('<details class="usage-details"><summary>Called from <span class="count">2</span></summary><ul class="usage-list">', $html);
        self::assertStringNotContainsString('in tests', $html);
        self::assertStringEndsWith("</ul></details>\n", $html);
        self::assertStringContainsString('<details class="usage-details" open>', (new UsageListHtml())->section($services, 'index.html', 'Called from', $list, true));
        self::assertSame('', (new UsageListHtml())->section($services, 'index.html', 'Called from', [], false));
    }

    public function testCallSectionListsOutgoingCallsInAClosedDetailsBlock(): void
    {
        $caller = new ClassLikeDoc('Demo\Caller', 'Caller', 'Demo', 'class', 'demo/pkg', 'src/Demo/Caller.php', 3, 12, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($caller);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$caller], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $usage = new Usage('Demo\Caller', 'boot', 'method-call', 'Demo\Widget', 'run', 'src/Demo/Widget.php', 30, false);

        $html = (new UsageListHtml())->callSection($services, 'index.html', 'Calls', [$usage]);

        self::assertStringStartsWith('<details class="usage-details"><summary>Calls <span class="count">1</span></summary><ul class="usage-list"><li>', $html);
        self::assertStringContainsString('<a href="demo/pkg/Demo/class.Caller.html" title="Demo\Caller">Caller::boot()</a>', $html);
        self::assertStringContainsString('<a class="usage-loc" href="src/src/Demo/Widget.php.html#L30">line 30</a>', $html);
        self::assertSame('', (new UsageListHtml())->callSection($services, 'index.html', 'Calls', []));
    }

    public function testShortNameKeepsTheTrailingSegmentOfAQualifiedName(): void
    {
        self::assertSame('Widget', (new UsageListHtml())->shortName('Demo\Core\Widget'));
        self::assertSame('Widget', (new UsageListHtml())->shortName('Widget'));
        self::assertSame('', (new UsageListHtml())->shortName(''));
    }
}
