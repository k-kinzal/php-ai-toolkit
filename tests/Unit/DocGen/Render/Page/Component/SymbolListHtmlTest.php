<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
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
use Toolkit\DocGen\Render\Diff\DiffHtml;
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\MarkdownInline;
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Render\Page\Component\SymbolRow;
use Toolkit\DocGen\Render\Page\SymbolIndex;
use Toolkit\DocGen\Render\PhpHighlighter;
use Toolkit\DocGen\Render\RenderKit;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Render\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Render\MarkdownInline
 * @uses \Toolkit\DocGen\Render\MarkdownRenderer
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Render\Page\SymbolIndex
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolRow
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
#[CoversClass(SymbolListHtml::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
final class SymbolListHtmlTest extends TestCase
{
    public function testGroupsRendersOneAnchoredSectionPerKindInKindOrder(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $rows = [
            new SymbolRow('class', 'Engine', 'Demo\Core\Engine', 'demo/pkg/Demo/Core/class.Engine.html', 'Engine summary.', []),
            new SymbolRow('interface', 'Runner', 'Demo\Core\Runner', 'demo/pkg/Demo/Core/interface.Runner.html', 'Runner contract.', []),
            new SymbolRow('class', 'Wheel', 'Demo\Core\Wheel', 'demo/pkg/Demo/Core/class.Wheel.html', '', []),
        ];

        $html = (new SymbolListHtml())->groups($services, 'demo/pkg/Demo/Core/index.html', $rows);

        self::assertStringStartsWith(
            '<section class="items" id="interfaces"><h2>Interfaces <span class="count">1</span><a class="anchor" href="#interfaces">§</a></h2>',
            $html,
        );
        self::assertStringContainsString(
            "</table></div></section>\n<section class=\"items\" id=\"classes\"><h2>Classes <span class=\"count\">2</span><a class=\"anchor\" href=\"#classes\">§</a></h2>",
            $html,
        );
        self::assertSame('', (new SymbolListHtml())->groups($services, 'demo/pkg/index.html', []));
    }

    public function testTableLinksEachRowToItsPageWithKindClassAndRenderedSummary(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $rows = [
            new SymbolRow('class', 'Engine', 'Demo\Core\Engine', 'demo/pkg/Demo/Core/class.Engine.html', 'Runs the `core` loop.', []),
        ];

        $html = (new SymbolListHtml())->table($services, 'demo/pkg/Demo/Core/index.html', $rows);

        self::assertSame(
            '<div class="table-wrap"><table class="item-table">'
            . '<tr><td><a class="item-name k-class" href="../../../../demo/pkg/Demo/Core/class.Engine.html">Engine</a></td>'
            . '<td class="item-summary">Runs the <code>core</code> loop.</td></tr>'
            . '</table></div>',
            $html,
        );
        self::assertSame('<div class="table-wrap"><table class="item-table"></table></div>', (new SymbolListHtml())->table($services, 'demo/pkg/index.html', []));
    }

    public function testTableAddsANamespaceCellWhenTheListingSpansNamespaces(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($engine);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$engine], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $rows = [new SymbolRow('class', 'Engine', 'Demo\Core\Engine', 'demo/pkg/Demo/Core/class.Engine.html', 'Engine summary.', [], 'Demo\Core')];

        self::assertSame(
            '<div class="table-wrap"><table class="item-table">'
            . '<tr><td><a class="item-name k-class" href="../../demo/pkg/Demo/Core/class.Engine.html">Engine</a></td>'
            . '<td class="item-ns"><a href="../../demo/pkg/Demo/Core/index.html">Demo\Core</a></td>'
            . '<td class="item-summary">Engine summary.</td></tr>'
            . '</table></div>',
            (new SymbolListHtml())->table($services, 'demo/pkg/all-items.html', $rows, true),
        );
    }

    public function testNamespaceOverviewListsEveryNamespaceOfTheListingSorted(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $text = new ClassLikeDoc('Demo\Util\Text', 'Text', 'Demo\Util', 'class', 'demo/pkg', 'src/Util/Text.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($engine);
        $table->registerClassLike($text);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$engine, $text], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $rows = [
            new SymbolRow('class', 'Text', 'Demo\Util\Text', 'demo/pkg/Demo/Util/class.Text.html', '', [], 'Demo\Util'),
            new SymbolRow('class', 'Engine', 'Demo\Core\Engine', 'demo/pkg/Demo/Core/class.Engine.html', '', [], 'Demo\Core'),
        ];

        self::assertSame(
            '<section><h2 id="namespaces">Namespaces<a class="anchor" href="#namespaces">§</a></h2>'
            . '<div class="table-wrap"><table class="symbol-table">'
            . '<tr><td><a href="../../demo/pkg/Demo/Core/index.html">Demo\Core</a></td>'
            . '<td class="ns-counts"> <span class="ns-count k-class">1 class</span></td></tr>'
            . '<tr><td><a href="../../demo/pkg/Demo/Util/index.html">Demo\Util</a></td>'
            . '<td class="ns-counts"> <span class="ns-count k-class">1 class</span></td></tr>'
            . "</table></div></section>\n",
            (new SymbolListHtml())->namespaceOverview($services, 'demo/pkg/layer.Domain.html', $rows),
        );
        self::assertSame('', (new SymbolListHtml())->namespaceOverview($services, 'demo/pkg/layer.Domain.html', []));
    }

    public function testNamespaceRowLabelsTheGlobalNamespaceAndSkipsUnresolvedPackages(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $rows = [new SymbolRow('class', 'Root', 'Root', 'demo/pkg/class.Root.html', '', [])];

        self::assertSame(
            '<tr><td>(global)</td><td class="ns-counts"> <span class="ns-count k-class">1 class</span></td></tr>',
            (new SymbolListHtml())->namespaceRow($services, 'demo/pkg/index.html', $rows),
        );
    }

    public function testKindCountsPluralisesEachKindInKindOrder(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $rows = [
            new SymbolRow('class', 'Engine', 'Demo\Engine', 'demo/pkg/class.Engine.html', '', []),
            new SymbolRow('class', 'Wheel', 'Demo\Wheel', 'demo/pkg/class.Wheel.html', '', []),
            new SymbolRow('interface', 'Runner', 'Demo\Runner', 'demo/pkg/interface.Runner.html', '', []),
        ];

        self::assertSame(
            ' <span class="ns-count k-interface">1 interface</span> <span class="ns-count k-class">2 classes</span>',
            (new SymbolListHtml())->kindCounts($services, $rows),
        );
        self::assertSame('', (new SymbolListHtml())->kindCounts($services, []));
    }

    public function testNamespaceCellLinksTheNamespaceListingOrStaysEmptyForTheGlobalNamespace(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $root = new ClassLikeDoc('Root', 'Root', '', 'class', 'demo/pkg', 'src/Root.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($engine);
        $table->registerClassLike($root);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$engine, $root], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $row = new SymbolRow('class', 'Engine', 'Demo\Core\Engine', 'demo/pkg/Demo/Core/class.Engine.html', '', [], 'Demo\Core');
        $global = new SymbolRow('class', 'Root', 'Root', 'demo/pkg/class.Root.html', '', []);

        self::assertSame(
            '<td class="item-ns"><a href="../../demo/pkg/Demo/Core/index.html">Demo\Core</a></td>',
            (new SymbolListHtml())->namespaceCell($services, 'demo/pkg/all-items.html', $row),
        );
        self::assertSame('<td class="item-ns"></td>', (new SymbolListHtml())->namespaceCell($services, 'demo/pkg/all-items.html', $global));
    }

    public function testNamespaceCellRendersPlainEscapedTextWhenThePackageCannotBeResolved(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $absent = new SymbolRow('class', 'Absent', 'Demo\Core\Absent', 'demo/pkg/Demo/Core/class.Absent.html', '', [], 'Demo\Core');

        self::assertSame(
            '<td class="item-ns">Demo\Core</td>',
            (new SymbolListHtml())->namespaceCell($services, 'demo/pkg/all-items.html', $absent),
        );
    }

    public function testPackageOfResolvesClassLikeAndFunctionOwnersAndFallsBackToNothing(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $make = new FunctionDoc('Demo\Core\make', 'make', 'Demo\Core', 'other/pkg', 'src/Core/functions.php', 7, 10, [], new TypeSignature('int', null), null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($engine);
        $table->registerFunction($make);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$engine], [$make], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            'demo/pkg',
            (new SymbolListHtml())->packageOf($services, new SymbolRow('class', 'Engine', 'Demo\Core\Engine', 'demo/pkg/Demo/Core/class.Engine.html', '', [], 'Demo\Core')),
        );
        self::assertSame(
            'other/pkg',
            (new SymbolListHtml())->packageOf($services, new SymbolRow('function', 'make', 'Demo\Core\make', 'other/pkg/Demo/Core/function.make.html', '', [], 'Demo\Core')),
        );
        self::assertSame(
            '',
            (new SymbolListHtml())->packageOf($services, new SymbolRow('class', 'Absent', 'Demo\Absent', 'demo/pkg/Demo/class.Absent.html', '', [], 'Demo')),
        );
    }

    public function testSectionsReturnsOneAnchorPerRenderedKind(): void
    {
        $rows = [
            new SymbolRow('function', 'make', 'Demo\make', 'demo/pkg/function.make.html', '', []),
            new SymbolRow('interface', 'Runner', 'Demo\Runner', 'demo/pkg/interface.Runner.html', '', []),
        ];

        self::assertSame(
            [
                ['id' => 'interfaces', 'label' => 'Interfaces', 'status' => DiffStatus::SAME],
                ['id' => 'functions', 'label' => 'Functions', 'status' => DiffStatus::SAME],
            ],
            (new SymbolListHtml())->sections($rows),
        );
        self::assertSame([], (new SymbolListHtml())->sections([]));
    }

    public function testStatusesListsTheDiffStateOfEveryRow(): void
    {
        $rows = [
            new SymbolRow('class', 'Fresh', 'Demo\Fresh', 'demo/pkg/class.Fresh.html', '', [], 'Demo', DiffStatus::ADDED),
            new SymbolRow('class', 'Kept', 'Demo\Kept', 'demo/pkg/class.Kept.html', '', [], 'Demo'),
        ];

        self::assertSame([DiffStatus::ADDED, DiffStatus::SAME], (new SymbolListHtml())->statuses($rows));
        self::assertSame([], (new SymbolListHtml())->statuses([]));
    }
}
