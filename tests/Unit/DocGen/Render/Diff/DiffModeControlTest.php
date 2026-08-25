<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Diff;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffIndex;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Doctest\AssertionScanner;
use Toolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Package\PackageGraph;
use Toolkit\DocGen\Render\Diff\DiffHtml;
use Toolkit\DocGen\Render\Diff\DiffModeControl;
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\MarkdownInline;
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\PhpHighlighter;
use Toolkit\DocGen\Render\RenderKit;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Render\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Render\MarkdownInline
 * @uses \Toolkit\DocGen\Render\MarkdownRenderer
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Render\PhpHighlighter
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
#[CoversClass(DiffModeControl::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(UsageIndex::class)]
final class DiffModeControlTest extends TestCase
{
    public function testRenderOffersTheThreeModesAndNamesTheComparedRevisions(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/project', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit(
            $model,
            new SiteUrl(),
            new HtmlText(),
            new PhpHighlighter(),
            new MarkdownRenderer(),
            new TypeHtml(null, new SiteUrl()),
            new DoctestExtractor(),
            new AssertionScanner(),
            new DiffHtml(new DiffIndex('2f0c1a2', 'working tree')),
        );

        $html = (new DiffModeControl())->render($services);

        self::assertStringContainsString('<span class="diff-range" title="Comparing 2f0c1a2 to working tree">', $html);
        self::assertStringContainsString('<div class="diff-modes" id="diff-modes" role="group" aria-label="Diff display mode">', $html);
        self::assertStringContainsString('<button type="button" class="diff-mode" data-diff-mode="off"', $html);
        self::assertStringContainsString('<button type="button" class="diff-mode" data-diff-mode="inline"', $html);
        self::assertStringContainsString('<button type="button" class="diff-mode" data-diff-mode="changes"', $html);
        self::assertStringEndsWith('</div>', $html);
    }

    public function testRenderShowsNothingOutsideAComparison(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/project', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit(
            $model,
            new SiteUrl(),
            new HtmlText(),
            new PhpHighlighter(),
            new MarkdownRenderer(),
            new TypeHtml(null, new SiteUrl()),
            new DoctestExtractor(),
            new AssertionScanner(),
        );

        self::assertSame('', (new DiffModeControl())->render($services));
    }
}
