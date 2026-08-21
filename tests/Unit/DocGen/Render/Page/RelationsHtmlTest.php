<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\Usage;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\Page\RelationsHtml;
use PhpAiToolkit\DocGen\Render\Page\UsageListHtml;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PhpAiToolkit\Doctest\Analysis\AssertionScanner;
use PhpAiToolkit\Doctest\Analysis\DoctestExtractor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RelationsHtml::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeKind::class)]
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
#[UsesClass(UsageListHtml::class)]
final class RelationsHtmlTest extends TestCase
{
    public function testBuildSectionsHierarchyRowsBeforeGroupedReferences(): void
    {
        $renderer = new ClassLikeDoc('Demo\Renderer', 'Renderer', 'Demo', 'interface', 'demo/pkg', 'src/Demo/Renderer.php', 3, 8, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 10, 20, false, true, [], ['Demo\Renderer'], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($renderer);
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([$renderer, $widget]);
        $usages = new UsageIndex();
        $usages->build([new Usage('Demo\Renderer', null, 'type', 'Demo\Widget', 'run', 'src/Demo/Widget.php', 15, false)]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$renderer, $widget], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $pagePath = 'demo/pkg/Demo/interface.Renderer.html';

        $html = (new RelationsHtml())->build($services, $pagePath, $renderer);

        self::assertStringStartsWith(
            '<section class="relations"><h2 id="relations">Relations<a class="anchor" href="#relations">§</a></h2>' . "\n"
            . '<details class="usage-details" open><summary>Implemented by <span class="count">1</span></summary><ul class="usage-list">'
            . '<li><a class="k-class" href="../../../demo/pkg/Demo/class.Widget.html" title="Demo\Widget">Widget</a>'
            . ' <a class="usage-loc" href="../../../src/src/Demo/Widget.php.html#L10">src/Demo/Widget.php:10</a></li>'
            . '</ul></details>' . "\n",
            $html,
        );
        self::assertStringContainsString('<summary>Type declarations <span class="count">1</span></summary>', $html);
        self::assertStringEndsWith("</section>\n", $html);
    }

    public function testBuildRendersNothingWithoutHierarchyOrReferences(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 10, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([$widget]);
        $usages = new UsageIndex();
        $usages->build([]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$widget], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $pagePath = 'demo/pkg/Demo/class.Widget.html';

        self::assertSame('', (new RelationsHtml())->build($services, $pagePath, $widget));
    }

    public function testHierarchyRowsListsImplementorsAndInterfaceExtendersOfAnInterface(): void
    {
        $renderer = new ClassLikeDoc('Demo\Renderer', 'Renderer', 'Demo', 'interface', 'demo/pkg', 'src/Demo/Renderer.php', 3, 8, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $advanced = new ClassLikeDoc('Demo\Advanced', 'Advanced', 'Demo', 'interface', 'demo/pkg', 'src/Demo/Advanced.php', 3, 8, false, false, ['Demo\Renderer'], [], [], [], [], [], [], null, null, [], false);
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 10, 20, false, true, [], ['Demo\Renderer'], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($renderer);
        $table->registerClassLike($advanced);
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([$renderer, $advanced, $widget]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$renderer, $advanced, $widget], [], $table, $hierarchy, new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new RelationsHtml())->hierarchyRows($services, 'demo/pkg/Demo/interface.Renderer.html', $renderer);

        self::assertStringStartsWith(
            '<details class="usage-details" open><summary>Implemented by <span class="count">1</span></summary><ul class="usage-list">'
            . '<li><a class="k-class" href="../../../demo/pkg/Demo/class.Widget.html" title="Demo\Widget">Widget</a>'
            . ' <a class="usage-loc" href="../../../src/src/Demo/Widget.php.html#L10">src/Demo/Widget.php:10</a></li>'
            . '</ul></details>' . "\n",
            $html,
        );
        self::assertStringContainsString(
            '<details class="usage-details" open><summary>Extended by <span class="count">1</span></summary><ul class="usage-list">'
            . '<li><a class="k-interface" href="../../../demo/pkg/Demo/interface.Advanced.html" title="Demo\Advanced">Advanced</a>'
            . ' <a class="usage-loc" href="../../../src/src/Demo/Advanced.php.html#L3">src/Demo/Advanced.php:3</a></li>'
            . '</ul></details>' . "\n",
            $html,
        );
    }

    public function testHierarchyRowsListsSubclassesOfAClassAndUsersOfATrait(): void
    {
        $base = new ClassLikeDoc('Demo\Base', 'Base', 'Demo', 'class', 'demo/pkg', 'src/Demo/Base.php', 3, 8, true, false, [], [], [], [], [], [], [], null, null, [], false);
        $helper = new ClassLikeDoc('Demo\Helper', 'Helper', 'Demo', 'trait', 'demo/pkg', 'src/Demo/Helper.php', 3, 8, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $child = new ClassLikeDoc('Demo\Child', 'Child', 'Demo', 'class', 'demo/pkg', 'src/Demo/Child.php', 10, 20, false, true, ['Demo\Base'], [], ['Demo\Helper'], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($base);
        $table->registerClassLike($helper);
        $table->registerClassLike($child);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([$base, $helper, $child]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$base, $helper, $child], [], $table, $hierarchy, new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            '<details class="usage-details" open><summary>Extended by <span class="count">1</span></summary><ul class="usage-list">'
            . '<li><a class="k-class" href="../../../demo/pkg/Demo/class.Child.html" title="Demo\Child">Child</a>'
            . ' <a class="usage-loc" href="../../../src/src/Demo/Child.php.html#L10">src/Demo/Child.php:10</a></li>'
            . '</ul></details>' . "\n",
            (new RelationsHtml())->hierarchyRows($services, 'demo/pkg/Demo/class.Base.html', $base),
        );
        self::assertSame(
            '<details class="usage-details" open><summary>Used by <span class="count">1</span></summary><ul class="usage-list">'
            . '<li><a class="k-class" href="../../../demo/pkg/Demo/class.Child.html" title="Demo\Child">Child</a>'
            . ' <a class="usage-loc" href="../../../src/src/Demo/Child.php.html#L10">src/Demo/Child.php:10</a></li>'
            . '</ul></details>' . "\n",
            (new RelationsHtml())->hierarchyRows($services, 'demo/pkg/Demo/trait.Helper.html', $helper),
        );
    }

    public function testSymbolSectionLinksKnownSymbolsAndFallsBackToPlainNames(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 10, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$widget], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            '<details class="usage-details" open><summary>Used by <span class="count">2</span></summary><ul class="usage-list">'
            . '<li><a class="k-class" href="demo/pkg/Demo/class.Widget.html" title="Demo\Widget">Widget</a>'
            . ' <a class="usage-loc" href="src/src/Demo/Widget.php.html#L10">src/Demo/Widget.php:10</a></li>'
            . '<li><code>Demo\Absent</code></li>'
            . '</ul></details>' . "\n",
            (new RelationsHtml())->symbolSection($services, 'index.html', 'Used by', ['Demo\Widget', 'Demo\Absent']),
        );
    }

    public function testSymbolSectionRendersNothingWithoutNames(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame('', (new RelationsHtml())->symbolSection($services, 'index.html', 'Used by', []));
    }

    public function testReferenceGroupsSkipsStructuralSelfAndDevReferences(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 10, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $usages = new UsageIndex();
        $usages->build([
            new Usage('Demo\Widget', null, 'extends', 'Demo\Other', null, 'src/Demo/Other.php', 3, false),
            new Usage('Demo\Widget', null, 'new', 'Demo\Widget', 'boot', 'src/Demo/Widget.php', 9, false),
            new Usage('Demo\Widget', null, 'new', 'Demo\Caller', 'boot', 'src/Demo/Caller.php', 14, false),
            new Usage('Demo\Widget', 'run', 'method-call', 'Demo\Caller', 'boot', 'src/Demo/Caller.php', 20, false),
            new Usage('Demo\Widget', null, 'new', 'Tests\WidgetTest', 'testRun', 'tests/WidgetTest.php', 12, true),
        ]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$widget], [], $table, new HierarchyIndex(), $usages, new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new RelationsHtml())->referenceGroups($services, 'demo/pkg/Demo/class.Widget.html', $widget);

        self::assertStringStartsWith('<details class="usage-details"><summary>Instantiated in <span class="count">1</span></summary>', $html);
        self::assertStringContainsString('<summary>Method calls <span class="count">1</span></summary>', $html);
        self::assertStringContainsString('src/Demo/Caller.php:14', $html);
        self::assertStringContainsString('src/Demo/Caller.php:20', $html);
        self::assertStringNotContainsString('src/Demo/Other.php', $html);
        self::assertStringNotContainsString('Widget.php:9', $html);
        self::assertStringNotContainsString('WidgetTest', $html);
    }
}
