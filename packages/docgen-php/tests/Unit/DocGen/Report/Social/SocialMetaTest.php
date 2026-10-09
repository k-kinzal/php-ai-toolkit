<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report\Social;

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
use Toolkit\DocGen\Report\SiteUrl;
use Toolkit\DocGen\Report\Social\SocialCard;
use Toolkit\DocGen\Report\Social\SocialCardText;
use Toolkit\DocGen\Report\Social\SocialMeta;
use Toolkit\DocGen\Report\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Report\Social\SocialMeta
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Report\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Report\HtmlText
 * @uses \Toolkit\DocGen\Report\MarkdownInline
 * @uses \Toolkit\DocGen\Report\MarkdownRenderer
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Report\PhpHighlighter
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Report\RenderKit
 * @uses \Toolkit\DocGen\Report\SiteUrl
 * @uses \Toolkit\DocGen\Report\Social\SocialCard
 * @uses \Toolkit\DocGen\Report\Social\SocialCardText
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Report\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
#[CoversClass(SocialMeta::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(DiffHtml::class)]
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
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialCardText::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(UsageIndex::class)]
final class SocialMetaTest extends TestCase
{
    public function testRenderProducesNothingUntilAProjectSaysWhereItIsPublished(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame('', (new SocialMeta())->render($kit, 'index.html', 'Overview', 'A demo.'));
    }

    public function testRenderCarriesTheCanonicalLinkAndThePreviewTags(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], 'https://example.github.io/demo');
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new SocialMeta())->render($kit, 'demo/pkg/Demo/class.Widget.html', 'Widget', 'Holds one widget.');

        self::assertStringContainsString('<link rel="canonical" href="https://example.github.io/demo/demo/pkg/Demo/class.Widget.html">', $html);
        self::assertStringContainsString('<meta name="description" content="Holds one widget.">', $html);
        self::assertStringContainsString('<meta property="og:type" content="website">', $html);
        self::assertStringContainsString('<meta property="og:site_name" content="Demo Docs">', $html);
        self::assertStringContainsString('<meta property="og:title" content="Widget — Demo Docs">', $html);
        self::assertStringContainsString('<meta property="og:description" content="Holds one widget.">', $html);
        self::assertStringContainsString('<meta property="og:url" content="https://example.github.io/demo/demo/pkg/Demo/class.Widget.html">', $html);
    }

    public function testRenderEscapesWhatItPrintsIntoAttributes(): void
    {
        $model = new ProjectModel('Demo "Docs"', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], 'https://example.github.io/demo');
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new SocialMeta())->render($kit, 'index.html', 'Overview', 'A <b>bold</b> "claim".');

        self::assertStringContainsString('content="Demo &quot;Docs&quot;"', $html);
        self::assertStringContainsString('content="A bold &quot;claim&quot;."', $html);
    }

    public function testRenderNamesTheDrawnCardWhereItCanBeDrawn(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], 'https://example.github.io/demo');
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new SocialMeta())->render($kit, 'index.html', 'Overview', 'A demo.');

        self::assertSame(
            (new SocialCard())->supported(),
            str_contains($html, '<meta property="og:image" content="https://example.github.io/demo/assets/og-image.png">'),
        );
        self::assertStringContainsString(
            (new SocialCard())->supported() ? '<meta name="twitter:card" content="summary_large_image">' : '<meta name="twitter:card" content="summary">',
            $html,
        );
    }

    public function testImageDescribesTheCardItNames(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], 'https://example.github.io/demo');
        $kit = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new SocialMeta())->image($kit, 'https://example.github.io/demo');

        self::assertSame(
            (new SocialCard())->supported(),
            str_contains($html, '<meta property="og:image:width" content="1200">')
                && str_contains($html, '<meta property="og:image:height" content="630">')
                && str_contains($html, '<meta property="og:image:alt" content="Demo Docs">'),
        );
    }

    public function testTagPrintsNothingWithoutContent(): void
    {
        $meta = new SocialMeta();

        self::assertSame("<meta name=\"description\" content=\"One line.\">\n", $meta->tag('name', 'description', 'One line.'));
        self::assertSame('', $meta->tag('name', 'description', ''));
    }

    public function testUrlNamesAnIndexPageByTheDirectoryItIndexes(): void
    {
        $meta = new SocialMeta();

        self::assertSame('https://example.com/demo/', $meta->url('https://example.com/demo', 'index.html'));
        self::assertSame('https://example.com/demo/pkg/', $meta->url('https://example.com/demo', 'pkg/index.html'));
        self::assertSame('https://example.com/demo/pkg/all-items.html', $meta->url('https://example.com/demo', 'pkg/all-items.html'));
    }

    public function testSummaryReadsAsOneShortLine(): void
    {
        $meta = new SocialMeta();

        self::assertSame('One sentence. And another.', $meta->summary("One sentence.\n And   another."));
        self::assertSame('', $meta->summary('   '));
    }

    public function testSummaryCutsWhatACardWouldNotShow(): void
    {
        $summary = (new SocialMeta())->summary(str_repeat('word ', 100));

        self::assertStringEndsWith('…', $summary);
        self::assertLessThan(strlen(str_repeat('word ', 100)), strlen($summary));
        self::assertLessThanOrEqual(SocialMeta::SUMMARY_LENGTH + 3, strlen($summary));
    }
}
