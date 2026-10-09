<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report\Diff;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Compare\DiffIndex;
use Toolkit\DocGen\Compare\DiffKey;
use Toolkit\DocGen\Compare\DiffLine;
use Toolkit\DocGen\Compare\DiffStatus;
use Toolkit\DocGen\Compare\LcsMatcher;
use Toolkit\DocGen\Compare\LineDiffer;
use Toolkit\DocGen\Report\Diff\DiffHtml;
use Toolkit\DocGen\Report\Diff\SourceDiffHtml;
use Toolkit\DocGen\Report\Doctest\AssertionScanner;
use Toolkit\DocGen\Report\Doctest\DoctestExtractor;
use Toolkit\DocGen\Report\HtmlText;
use Toolkit\DocGen\Report\MarkdownInline;
use Toolkit\DocGen\Report\MarkdownRenderer;
use Toolkit\DocGen\Report\PhpHighlighter;
use Toolkit\DocGen\Report\RenderKit;
use Toolkit\DocGen\Report\SiteUrl;
use Toolkit\DocGen\Report\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Report\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Compare\DiffIndex
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Compare\DiffLine
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Report\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Report\HtmlText
 * @uses \Toolkit\DocGen\Compare\LcsMatcher
 * @uses \Toolkit\DocGen\Compare\LineDiffer
 * @uses \Toolkit\DocGen\Report\MarkdownInline
 * @uses \Toolkit\DocGen\Report\MarkdownRenderer
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Report\PhpHighlighter
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Report\RenderKit
 * @uses \Toolkit\DocGen\Report\SiteUrl
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Report\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
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
