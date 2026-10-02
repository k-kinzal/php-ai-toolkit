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
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCase as ReferenceTestCase;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Package\PackageGraph;
use Toolkit\DocGen\Render\Diff\DiffHtml;
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\MarkdownInline;
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\Page\Component\TestCaseHtml;
use Toolkit\DocGen\Render\PhpHighlighter;
use Toolkit\DocGen\Render\RenderKit;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Render\Page\Component\TestCaseHtml
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Render\MarkdownInline
 * @uses \Toolkit\DocGen\Render\MarkdownRenderer
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCase
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
#[CoversClass(TestCaseHtml::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(ReferenceTestCase::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(UsageIndex::class)]
final class TestCaseHtmlTest extends TestCase
{
    public function testSectionWrapsTestCasesInCollapsibleDetailsWithCount(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $testCases = [
            new ReferenceTestCase('Tests\Unit\EngineTest', 'testRun', 'tests/Unit/EngineTest.php', 42, ReferenceTestCase::ORIGIN_CALL),
            new ReferenceTestCase('Tests\Unit\WheelTest', 'testSpin', null, null, ReferenceTestCase::ORIGIN_COVERAGE),
        ];

        $html = (new TestCaseHtml())->section($services, 'demo/pkg/Demo/class.Engine.html', $testCases);

        self::assertStringStartsWith(
            '<details class="usage-details test-cases"><summary>Test cases <span class="count">2</span></summary><ul class="usage-list">',
            $html,
        );
        self::assertStringContainsString('<a href="../../../src/tests/Unit/EngineTest.php.html#L42"', $html);
        self::assertStringContainsString("</ul></details>\n", $html);
    }

    public function testSectionRendersNothingWithoutTestCases(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame('', (new TestCaseHtml())->section($services, 'demo/pkg/index.html', []));
    }

    public function testListRendersExpandedItemsWithoutDetailsWrapper(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $testCases = [new ReferenceTestCase('Tests\Unit\EngineTest', 'testRun', null, null, ReferenceTestCase::ORIGIN_BOTH)];

        $html = (new TestCaseHtml())->list($services, 'demo/pkg/index.html', $testCases);

        self::assertSame(
            '<ul class="usage-list"><li><code title="Tests\Unit\EngineTest">EngineTest::testRun</code>'
            . ' <span class="usage-kind">covers and calls</span></li></ul>' . "\n",
            $html,
        );
        self::assertSame('', (new TestCaseHtml())->list($services, 'demo/pkg/index.html', []));
    }

    public function testSubSectionLabelsAndCountsOneGroupOfTestCases(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $testCases = [
            new ReferenceTestCase('Tests\Unit\EngineTest', 'testRun', null, null, ReferenceTestCase::ORIGIN_CALL),
            new ReferenceTestCase('Tests\Unit\EngineTest', 'testStop', null, null, ReferenceTestCase::ORIGIN_COVERAGE),
        ];

        self::assertSame(
            '<details class="usage-details test-cases" open><summary>Dedicated tests <span class="count">2</span></summary>'
            . '<ul class="usage-list">'
            . '<li><code title="Tests\Unit\EngineTest">EngineTest::testRun</code> <span class="usage-kind">calls</span></li>'
            . '<li><code title="Tests\Unit\EngineTest">EngineTest::testStop</code> <span class="usage-kind">covers</span></li>'
            . '</ul>' . "\n" . '</details>' . "\n",
            (new TestCaseHtml())->subSection($services, 'demo/pkg/index.html', 'Dedicated tests', $testCases, true),
        );
        self::assertStringStartsWith(
            '<details class="usage-details test-cases"><summary>Other tests reaching this symbol <span class="count">2</span></summary>',
            (new TestCaseHtml())->subSection($services, 'demo/pkg/index.html', 'Other tests reaching this symbol', $testCases, false),
        );
    }

    public function testSubSectionRendersNothingWithoutTestCases(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame('', (new TestCaseHtml())->subSection($services, 'demo/pkg/index.html', 'Dedicated tests', [], true));
    }

    public function testItemLinksToTheTestSourceLineWhenKnown(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $testCase = new ReferenceTestCase('Tests\Unit\EngineTest', 'testRun', 'tests/Unit/EngineTest.php', 42, ReferenceTestCase::ORIGIN_CALL);

        self::assertSame(
            '<a href="../../src/tests/Unit/EngineTest.php.html#L42" title="Tests\Unit\EngineTest"><code>EngineTest::testRun</code></a>'
            . ' <span class="usage-kind">calls</span>',
            (new TestCaseHtml())->item($services, 'demo/pkg/index.html', $testCase),
        );
    }

    public function testItemFallsBackToPlainCodeWithoutFileAndMethod(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $testCase = new ReferenceTestCase('EngineTest', null, null, null, ReferenceTestCase::ORIGIN_COVERAGE);

        self::assertSame(
            '<code title="EngineTest">EngineTest</code> <span class="usage-kind">covers</span>',
            (new TestCaseHtml())->item($services, 'demo/pkg/index.html', $testCase),
        );
    }

    public function testItemOmitsTheLineAnchorWhenOnlyTheFileIsKnown(): void
    {
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $testCase = new ReferenceTestCase('Tests\Unit\EngineTest', 'testRun', 'tests/Unit/EngineTest.php', null, ReferenceTestCase::ORIGIN_COVERAGE);

        self::assertStringContainsString(
            '<a href="src/tests/Unit/EngineTest.php.html" title="Tests\Unit\EngineTest">',
            (new TestCaseHtml())->item($services, 'index.html', $testCase),
        );
    }

    public function testOriginLabelNamesCoverageCallAndCombinedEvidence(): void
    {
        self::assertSame('covers', (new TestCaseHtml())->originLabel(ReferenceTestCase::ORIGIN_COVERAGE));
        self::assertSame('calls', (new TestCaseHtml())->originLabel(ReferenceTestCase::ORIGIN_CALL));
        self::assertSame('covers and calls', (new TestCaseHtml())->originLabel(ReferenceTestCase::ORIGIN_BOTH));
    }
}
