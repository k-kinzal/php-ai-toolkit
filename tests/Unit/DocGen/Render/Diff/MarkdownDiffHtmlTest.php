<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Diff;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\LcsMatcher;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PhpAiToolkit\DocGen\Render\Diff\MarkdownDiffHtml;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PhpAiToolkit\Doctest\Analysis\AssertionScanner;
use PhpAiToolkit\Doctest\Analysis\DoctestExtractor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
