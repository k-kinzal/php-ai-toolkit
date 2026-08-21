<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Diff;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffLine;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\LcsMatcher;
use PhpAiToolkit\DocGen\Analysis\Diff\LineDiffer;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PhpAiToolkit\DocGen\Render\Diff\SourceDiffHtml;
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

#[CoversClass(SourceDiffHtml::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffLine::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(LcsMatcher::class)]
#[UsesClass(LineDiffer::class)]
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
final class SourceDiffHtmlTest extends TestCase
{
    public function testListingNumbersTheHeadRevisionAndKeepsTheLinesItLost(): void
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

        $html = (new SourceDiffHtml())->listing($services, "<?php\n\$gone = 1;\n", "<?php\n\$fresh = 2;\n");

        self::assertStringContainsString('<span class="src-line" id="L1" data-diff="same">', $html);
        self::assertStringContainsString('<span class="src-line" data-diff="removed"><span class="ln">2</span>', $html);
        self::assertStringContainsString('<span class="src-line" id="L2" data-diff="added"><a class="ln" href="#L2">2</a>', $html);
        self::assertStringContainsString('<span class="tok-var">$fresh</span>', $html);
        self::assertStringContainsString('<span class="tok-var">$gone</span>', $html);
    }

    public function testListingMarksAWholeFileTheRevisionAddedOrDropped(): void
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
        $diffHtml = new SourceDiffHtml();

        self::assertStringContainsString('data-diff="added"', $diffHtml->listing($services, null, '<?php'));
        self::assertStringNotContainsString('data-diff="removed"', $diffHtml->listing($services, null, '<?php'));
        self::assertStringContainsString('data-diff="removed"', $diffHtml->listing($services, '<?php', null));
    }

    public function testLineAnchorsOnlyTheLinesTheHeadRevisionStillHas(): void
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
        $diffHtml = new SourceDiffHtml();

        self::assertSame(
            '<span class="src-line" id="L4" data-diff="same"><a class="ln" href="#L4">4</a>code</span>' . "\n",
            $diffHtml->line($services, new DiffLine(DiffStatus::SAME, 'code', 3, 4)),
        );
        self::assertSame(
            '<span class="src-line" data-diff="removed"><span class="ln">3</span>gone</span>' . "\n",
            $diffHtml->line($services, new DiffLine(DiffStatus::REMOVED, 'gone', 3, null)),
        );
    }
}
