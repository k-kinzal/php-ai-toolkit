<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Diff;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Diff\DiffIndex;
use Toolkit\DocGen\Diff\DiffKey;
use Toolkit\DocGen\Diff\DiffLine;
use Toolkit\DocGen\Diff\DiffStatus;
use Toolkit\DocGen\Diff\LcsMatcher;
use Toolkit\DocGen\Diff\LineDiffer;
use Toolkit\DocGen\Model\Package\PackageGraph;
use Toolkit\DocGen\Model\ProjectModel;
use Toolkit\DocGen\Model\Reference\HierarchyIndex;
use Toolkit\DocGen\Model\Reference\SymbolTable;
use Toolkit\DocGen\Model\Reference\TestCaseIndex;
use Toolkit\DocGen\Model\Reference\UsageIndex;
use Toolkit\DocGen\Render\Diff\DiffHtml;
use Toolkit\DocGen\Render\Diff\SourceDiffHtml;
use Toolkit\DocGen\Render\Doctest\AssertionScanner;
use Toolkit\DocGen\Render\Doctest\DoctestExtractor;
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\MarkdownInline;
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\PhpHighlighter;
use Toolkit\DocGen\Render\RenderKit;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Render\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Render\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Diff\DiffKey
 * @uses \Toolkit\DocGen\Diff\DiffLine
 * @uses \Toolkit\DocGen\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Render\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Model\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Diff\LcsMatcher
 * @uses \Toolkit\DocGen\Diff\LineDiffer
 * @uses \Toolkit\DocGen\Render\MarkdownInline
 * @uses \Toolkit\DocGen\Render\MarkdownRenderer
 * @uses \Toolkit\DocGen\Model\Package\PackageGraph
 * @uses \Toolkit\DocGen\Render\PhpHighlighter
 * @uses \Toolkit\DocGen\Model\ProjectModel
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Model\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Model\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Model\Reference\UsageIndex
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
