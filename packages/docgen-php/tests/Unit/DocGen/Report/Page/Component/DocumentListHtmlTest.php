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
use Toolkit\DocGen\Discovery\MarkdownDoc;
use Toolkit\DocGen\Report\Diff\DiffHtml;
use Toolkit\DocGen\Report\Doctest\AssertionScanner;
use Toolkit\DocGen\Report\Doctest\DoctestExtractor;
use Toolkit\DocGen\Report\HtmlText;
use Toolkit\DocGen\Report\MarkdownInline;
use Toolkit\DocGen\Report\MarkdownLinks;
use Toolkit\DocGen\Report\MarkdownRenderer;
use Toolkit\DocGen\Report\Page\Component\DocumentListHtml;
use Toolkit\DocGen\Report\PhpHighlighter;
use Toolkit\DocGen\Report\RenderKit;
use Toolkit\DocGen\Report\SiteUrl;
use Toolkit\DocGen\Report\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Report\Page\Component\DocumentListHtml
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Report\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Report\HtmlText
 * @uses \Toolkit\DocGen\Discovery\MarkdownDoc
 * @uses \Toolkit\DocGen\Report\MarkdownInline
 * @uses \Toolkit\DocGen\Report\MarkdownLinks
 * @uses \Toolkit\DocGen\Report\MarkdownRenderer
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Report\RenderKit
 * @uses \Toolkit\DocGen\Report\SiteUrl
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Report\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
#[CoversClass(DocumentListHtml::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownDoc::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownLinks::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(UsageIndex::class)]
final class DocumentListHtmlTest extends TestCase
{
    public function testDocumentsKeepOnlyTheRequestedPackage(): void
    {
        $own = new MarkdownDoc('demo/pkg', 'README.md', 'README.md', 'Demo');
        $foreign = new MarkdownDoc('other/pkg', 'README.md', 'other/README.md', 'Other');
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$own, $foreign]);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame([$own], (new DocumentListHtml())->documents($services, 'demo/pkg'));
        self::assertSame([], (new DocumentListHtml())->documents($services, 'absent/pkg'));
    }

    public function testPathsListsTheDocumentPathsOfThePackage(): void
    {
        $own = new MarkdownDoc('demo/pkg', 'README.md', 'README.md', 'Demo');
        $foreign = new MarkdownDoc('other/pkg', 'docs/other.md', 'other/docs/other.md', 'Other');
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$own, $foreign]);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(['README.md'], (new DocumentListHtml())->paths($services, 'demo/pkg'));
        self::assertSame([], (new DocumentListHtml())->paths($services, 'absent/pkg'));
    }

    public function testLinksResolveAgainstTheDocumentsOfThePackage(): void
    {
        $guide = new MarkdownDoc('demo/pkg', 'docs/guide.md', 'docs/guide.md', 'Guide');
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$guide]);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $links = (new DocumentListHtml())->links($services, 'demo/pkg/index.html', 'demo/pkg', '');

        self::assertSame('../../demo/pkg/doc/docs/guide.md.html', $links->resolve('docs/guide.md'));
    }

    public function testSectionListsEveryDocumentWithItsPath(): void
    {
        $readme = new MarkdownDoc('demo/pkg', 'README.md', 'README.md', 'Demo');
        $guide = new MarkdownDoc('demo/pkg', 'docs/guide.md', 'docs/guide.md', 'Guide');
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$readme, $guide]);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            '<section><h2 id="documents">Documents <span class="count">2</span><a class="anchor" href="#documents">§</a></h2>'
            . '<div class="table-wrap"><table class="symbol-table">'
            . '<tr><td><a href="../../demo/pkg/doc/README.md.html">Demo</a></td><td class="item-ns">README.md</td></tr>'
            . '<tr><td><a href="../../demo/pkg/doc/docs/guide.md.html">Guide</a></td><td class="item-ns">docs/guide.md</td></tr>'
            . "</table></div></section>\n",
            (new DocumentListHtml())->section($services, 'demo/pkg/index.html', 'demo/pkg'),
        );
        self::assertSame('', (new DocumentListHtml())->section($services, 'demo/pkg/index.html', 'absent/pkg'));
    }
}
