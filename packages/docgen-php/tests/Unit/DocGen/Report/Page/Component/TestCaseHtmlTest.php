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
use Toolkit\DocGen\Analysis\Reference\TestCase as ReferenceTestCase;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Compare\DiffKey;
use Toolkit\DocGen\Compare\DiffStatus;
use Toolkit\DocGen\Report\Diff\DiffHtml;
use Toolkit\DocGen\Report\Doctest\AssertionScanner;
use Toolkit\DocGen\Report\Doctest\DoctestExtractor;
use Toolkit\DocGen\Report\HtmlText;
use Toolkit\DocGen\Report\MarkdownInline;
use Toolkit\DocGen\Report\MarkdownRenderer;
use Toolkit\DocGen\Report\Page\Component\TestCaseHtml;
use Toolkit\DocGen\Report\PhpHighlighter;
use Toolkit\DocGen\Report\RenderKit;
use Toolkit\DocGen\Report\SiteUrl;
use Toolkit\DocGen\Report\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Report\Page\Component\TestCaseHtml
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Report\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Report\HtmlText
 * @uses \Toolkit\DocGen\Report\MarkdownInline
 * @uses \Toolkit\DocGen\Report\MarkdownRenderer
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCase
 * @uses \Toolkit\DocGen\Report\RenderKit
 * @uses \Toolkit\DocGen\Report\SiteUrl
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Report\TypeHtml
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
