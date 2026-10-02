<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Diff;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffIndex;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Diff\LcsMatcher;
use Toolkit\DocGen\Analysis\Doctest\AssertionScanner;
use Toolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Package\PackageGraph;
use Toolkit\DocGen\Render\Diff\DiffHtml;
use Toolkit\DocGen\Render\Diff\MarkdownDiffHtml;
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\MarkdownInline;
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\PhpHighlighter;
use Toolkit\DocGen\Render\RenderKit;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Render\Diff\MarkdownDiffHtml
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Analysis\Diff\LcsMatcher
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
#[CoversClass(MarkdownDiffHtml::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(LcsMatcher::class)]
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
final class MarkdownDiffHtmlTest extends TestCase
{
    public function testRenderMarksTheBlocksARevisionAddedChangedOrDropped(): void
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
            new DiffHtml(new DiffIndex('main', 'HEAD')),
        );

        $html = (new MarkdownDiffHtml())->render(
            $services,
            new MarkdownRenderer(),
            "# Guide\n\nOld wording.\n",
            "# Guide\n\nNew wording.\n",
            null,
        );

        self::assertStringContainsString('<div class="doc-block" data-diff="same"><h2>Guide</h2>', $html);
        self::assertStringContainsString('<div class="doc-block" data-diff="removed"><p>Old wording.</p>', $html);
        self::assertStringContainsString('<div class="doc-block" data-diff="added"><p>New wording.</p>', $html);
    }

    public function testRenderShowsAWholeDocumentOnlyOneRevisionHas(): void
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
            new DiffHtml(new DiffIndex('main', 'HEAD')),
        );
        $diffHtml = new MarkdownDiffHtml();

        self::assertSame(
            '<div class="doc-block" data-diff="added"><p>Fresh.</p>' . "\n" . '</div>',
            $diffHtml->render($services, new MarkdownRenderer(), null, "Fresh.\n", null),
        );
        self::assertSame(
            '<div class="doc-block" data-diff="removed"><p>Gone.</p>' . "\n" . '</div>',
            $diffHtml->render($services, new MarkdownRenderer(), "Gone.\n", null, null),
        );
    }

    public function testBlockPicksTheRevisionTheOperationPointsAt(): void
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
            new DiffHtml(new DiffIndex('main', 'HEAD')),
        );
        $diffHtml = new MarkdownDiffHtml();
        $base = [['source' => 'old', 'html' => '<p>old</p>']];
        $head = [['source' => 'new', 'html' => '<p>new</p>']];

        self::assertSame('<div class="doc-block" data-diff="removed"><p>old</p></div>', $diffHtml->block($services, $base, $head, ['base' => 0, 'head' => null]));
        self::assertSame('<div class="doc-block" data-diff="added"><p>new</p></div>', $diffHtml->block($services, $base, $head, ['base' => null, 'head' => 0]));
        self::assertSame('<div class="doc-block" data-diff="same"><p>new</p></div>', $diffHtml->block($services, $base, $head, ['base' => 0, 'head' => 0]));
        self::assertSame('', $diffHtml->block($services, $base, $head, ['base' => null, 'head' => null]));
        self::assertSame('', $diffHtml->block($services, $base, $head, ['base' => null, 'head' => 9]));
    }

    public function testWrapCarriesTheStateOfOneRenderedBlock(): void
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
            new DiffHtml(new DiffIndex('main', 'HEAD')),
        );

        self::assertSame(
            '<div class="doc-block" data-diff="modified"><p>body</p></div>',
            (new MarkdownDiffHtml())->wrap($services, DiffStatus::MODIFIED, '<p>body</p>'),
        );
    }

    public function testBlocksSplitADocumentIntoTheBlocksTheRendererProduces(): void
    {
        $markdown = <<<'MARKDOWN'
# Guide

A paragraph.

```php
echo 1;
```

- one
- two
MARKDOWN;

        $blocks = (new MarkdownDiffHtml())->blocks(new MarkdownRenderer(), $markdown, null);

        self::assertCount(4, $blocks);
        self::assertSame('# Guide', $blocks[0]['source']);
        self::assertSame("```php\necho 1;\n```", $blocks[2]['source']);
        self::assertStringContainsString('<h2>Guide</h2>', $blocks[0]['html']);
        self::assertStringContainsString('<ul><li>one</li>', $blocks[3]['html']);
    }

    public function testBlocksJoinToTheSameHtmlThePlainRendererProduces(): void
    {
        $markdown = "# Guide\n\nA paragraph.\n\n| a | b |\n| - | - |\n| 1 | 2 |\n\n> quoted\n";
        $blocks = (new MarkdownDiffHtml())->blocks(new MarkdownRenderer(), $markdown, null);

        self::assertSame(
            (new MarkdownRenderer())->render($markdown),
            $blocks[0]['html'] . $blocks[1]['html'] . $blocks[2]['html'] . $blocks[3]['html'],
        );
    }

    public function testSourcesListsTheSourceTextOfEveryBlock(): void
    {
        self::assertSame(
            ['a', 'b'],
            (new MarkdownDiffHtml())->sources([['source' => 'a', 'html' => '<p>a</p>'], ['source' => 'b', 'html' => '<p>b</p>']]),
        );
        self::assertSame([], (new MarkdownDiffHtml())->sources([]));
    }
}
