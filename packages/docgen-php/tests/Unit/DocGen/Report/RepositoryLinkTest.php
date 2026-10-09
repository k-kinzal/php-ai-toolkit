<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Report\Diff\DiffHtml;
use Toolkit\DocGen\Report\Doctest\AssertionScanner;
use Toolkit\DocGen\Report\Doctest\DoctestExtractor;
use Toolkit\DocGen\Report\HtmlText;
use Toolkit\DocGen\Report\MarkdownInline;
use Toolkit\DocGen\Report\MarkdownRenderer;
use Toolkit\DocGen\Report\PhpHighlighter;
use Toolkit\DocGen\Report\RenderKit;
use Toolkit\DocGen\Report\RepositoryLink;
use Toolkit\DocGen\Report\SiteUrl;
use Toolkit\DocGen\Report\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Report\RepositoryLink
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Report\HtmlText
 * @uses \Toolkit\DocGen\Report\MarkdownInline
 * @uses \Toolkit\DocGen\Report\MarkdownRenderer
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Report\RenderKit
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Report\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
#[CoversClass(RepositoryLink::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(UsageIndex::class)]
final class RepositoryLinkTest extends TestCase
{
    public function testTopbarNamesTheHostTheLinkLeavesTheSiteFor(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, 'https://github.com/example/project');
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            '<a class="repo-link" href="https://github.com/example/project" title="Repository: https://github.com/example/project" rel="noreferrer">github.com</a>' . "\n",
            (new RepositoryLink())->topbar($kit),
        );
    }

    public function testTopbarRendersNothingForASiteThatNamesNoRepository(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame('', (new RepositoryLink())->topbar($kit));
    }

    public function testFullWritesTheAddressWithoutItsScheme(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            '<a class="repo-link" href="https://github.com/example/project" title="Repository: https://github.com/example/project" rel="noreferrer">github.com/example/project</a>',
            (new RepositoryLink())->full($kit, 'https://github.com/example/project'),
        );
    }

    public function testLinkEscapesTheAddressAndTheTextItIsWrittenWith(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            '<a class="repo-link" href="https://git.example.com/a&amp;b" title="Repository: https://git.example.com/a&amp;b" rel="noreferrer">a&lt;b</a>',
            (new RepositoryLink())->link($kit, 'https://git.example.com/a&b', 'a<b'),
        );
    }

    public function testHostFallsBackToTheWholeAddressWhenThereIsNoneToRead(): void
    {
        self::assertSame('github.com', (new RepositoryLink())->host('https://github.com/example/project'));
        self::assertSame('/example/project', (new RepositoryLink())->host('/example/project'));
    }

    public function testLabelDropsTheSchemeAndTheCommonHostPrefix(): void
    {
        self::assertSame('github.com/example/project', (new RepositoryLink())->label('https://github.com/example/project'));
        self::assertSame('git.example.com/project', (new RepositoryLink())->label('http://www.git.example.com/project'));
    }
}
