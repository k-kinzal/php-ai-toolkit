<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Coverage\CoverageIndex;
use Toolkit\DocGen\Analysis\Coverage\MethodCoverage;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Diff\LineDiffer;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Analysis\Doctest\AssertionLine;
use Toolkit\DocGen\Analysis\Doctest\AssertionScanner;
use Toolkit\DocGen\Analysis\Doctest\DocExample;
use Toolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\ClassLikeKind;
use Toolkit\DocGen\Analysis\Model\ConstantDoc;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\DocTag;
use Toolkit\DocGen\Analysis\Model\EnumCaseDoc;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;
use Toolkit\DocGen\Analysis\Model\MethodDoc;
use Toolkit\DocGen\Analysis\Model\ParameterDoc;
use Toolkit\DocGen\Analysis\Model\PropertyDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\Parse\AstParser;
use Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder;
use Toolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use Toolkit\DocGen\Analysis\Parse\FileSymbolCollector;
use Toolkit\DocGen\Analysis\Parse\FileSymbols;
use Toolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use Toolkit\DocGen\Analysis\Parse\ParameterModifiers;
use Toolkit\DocGen\Analysis\Parse\PhpParserBridge;
use Toolkit\DocGen\Analysis\Parse\SymbolContext;
use Toolkit\DocGen\Analysis\Parse\UseMapCollector;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCase as ReferenceTestCase;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\Usage;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Filesystem\SiteFileWriter;
use Toolkit\DocGen\Package\ComposerManifest;
use Toolkit\DocGen\Package\DiscoveredPackage;
use Toolkit\DocGen\Package\PackageGraph;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkerPool;
use Toolkit\DocGen\Parallel\WorkScheduler;
use Toolkit\DocGen\Render\AssetPublisher;
use Toolkit\DocGen\Render\Diff\DiffHtml;
use Toolkit\DocGen\Render\Diff\MarkdownDiffHtml;
use Toolkit\DocGen\Render\Diff\SourceDiffHtml;
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\MarkdownInline;
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\Page\AllItemsPage;
use Toolkit\DocGen\Render\Page\ClassLikePage;
use Toolkit\DocGen\Render\Page\Component\BreadcrumbHtml;
use Toolkit\DocGen\Render\Page\Component\DocTextHtml;
use Toolkit\DocGen\Render\Page\Component\ExampleHtml;
use Toolkit\DocGen\Render\Page\Component\GraphSvg;
use Toolkit\DocGen\Render\Page\Component\MemberHtml;
use Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml;
use Toolkit\DocGen\Render\Page\Component\RelationsHtml;
use Toolkit\DocGen\Render\Page\Component\SidebarHtml;
use Toolkit\DocGen\Render\Page\Component\SignatureHtml;
use Toolkit\DocGen\Render\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Render\Page\Component\TestCaseHtml;
use Toolkit\DocGen\Render\Page\Component\UsageListHtml;
use Toolkit\DocGen\Render\Page\DocumentPage;
use Toolkit\DocGen\Render\Page\FunctionPage;
use Toolkit\DocGen\Render\Page\IndexPage;
use Toolkit\DocGen\Render\Page\LayerPage;
use Toolkit\DocGen\Render\Page\NamespacePage;
use Toolkit\DocGen\Render\Page\PackagePage;
use Toolkit\DocGen\Render\Page\SourcePage;
use Toolkit\DocGen\Render\PageChrome;
use Toolkit\DocGen\Render\PhpHighlighter;
use Toolkit\DocGen\Render\RenderKit;
use Toolkit\DocGen\Render\SearchIndexBuilder;
use Toolkit\DocGen\Render\Signature\PageSignature;
use Toolkit\DocGen\Render\Signature\SidebarDigest;
use Toolkit\DocGen\Render\SiteRenderer;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\Social\SocialCard;
use Toolkit\DocGen\Render\Social\SocialMeta;
use Toolkit\DocGen\Render\TypeHtml;
use Toolkit\DocGen\Render\TypeRenderContext;

/**
 * @covers \Toolkit\DocGen\Render\Page\Component\MemberHtml
 * @uses \Toolkit\DocGen\Render\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionLine
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\AssetPublisher
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Render\Page\Component\BreadcrumbHtml
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeKind
 * @uses \Toolkit\DocGen\Render\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ConstantDoc
 * @uses \Toolkit\DocGen\Analysis\Coverage\CoverageIndex
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Analysis\Model\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Analysis\Doctest\DocExample
 * @uses \Toolkit\DocGen\Analysis\Model\DocTag
 * @uses \Toolkit\DocGen\Render\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Render\Page\DocumentPage
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\EnumCaseDoc
 * @uses \Toolkit\DocGen\Render\Page\Component\ExampleHtml
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \Toolkit\DocGen\Render\Page\FunctionPage
 * @uses \Toolkit\DocGen\Render\Page\Component\GraphSvg
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Render\Page\IndexPage
 * @uses \Toolkit\DocGen\Render\Page\LayerPage
 * @uses \Toolkit\DocGen\Analysis\Diff\LineDiffer
 * @uses \Toolkit\DocGen\Render\Diff\MarkdownDiffHtml
 * @uses \Toolkit\DocGen\Render\MarkdownInline
 * @uses \Toolkit\DocGen\Render\MarkdownRenderer
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Coverage\MethodCoverage
 * @uses \Toolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \Toolkit\DocGen\Render\Page\NamespacePage
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Render\Page\PackagePage
 * @uses \Toolkit\DocGen\Render\PageChrome
 * @uses \Toolkit\DocGen\Render\Signature\PageSignature
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Render\PhpHighlighter
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\PropertyDoc
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCase
 * @uses \Toolkit\DocGen\Render\Page\Component\RelationsHtml
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Render\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Render\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Render\Page\Component\SignatureHtml
 * @uses \Toolkit\DocGen\Filesystem\SiteFileWriter
 * @uses \Toolkit\DocGen\Render\SiteRenderer
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Render\Social\SocialCard
 * @uses \Toolkit\DocGen\Render\Social\SocialMeta
 * @uses \Toolkit\DocGen\Render\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Render\Page\SourcePage
 * @uses \Toolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Render\Page\Component\TestCaseHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Render\TypeRenderContext
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\Usage
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Render\Page\Component\UsageListHtml
 * @uses \Toolkit\DocGen\Analysis\Parse\UseMapCollector
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 */
#[CoversClass(MemberHtml::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionLine::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(CoverageIndex::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocExample::class)]
#[UsesClass(DocTag::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentPage::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(EnumCaseDoc::class)]
#[UsesClass(ExampleHtml::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(FunctionPage::class)]
#[UsesClass(GraphSvg::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(IndexPage::class)]
#[UsesClass(LayerPage::class)]
#[UsesClass(LineDiffer::class)]
#[UsesClass(MarkdownDiffHtml::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(MethodCoverage::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NamespacePage::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PackagePage::class)]
#[UsesClass(PageChrome::class)]
#[UsesClass(PageSignature::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PrivateSurfaceHtml::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyDoc::class)]
#[UsesClass(ReferenceTestCase::class)]
#[UsesClass(RelationsHtml::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarDigest::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SignatureHtml::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SourceDiffHtml::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseHtml::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeRenderContext::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(Usage::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UsageListHtml::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
final class MemberHtmlTest extends TestCase
{
    public function testMethodRendersAnchorSignatureSourceLinkAndCallers(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public function run(): int
    {
        return 1;
    }
}

class Caller
{
    public function boot(): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $method = $widget->methods[0];
        $table = new SymbolTable();
        $table->registerClassLike($symbols->classLikes[0]);
        $table->registerClassLike($symbols->classLikes[1]);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([new Usage('Demo\Widget', 'run', 'method-call', 'Demo\Caller', 'boot', 'src/Demo/Caller.php', 12, false)]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $pagePath = 'demo/pkg/Demo/class.Widget.html';
        $context = new TypeRenderContext($pagePath, 'Demo', [], [], [], $table);

        $html = (new MemberHtml())->method($services, $pagePath, $widget, $method, $context);

        self::assertStringContainsString('<div class="member" id="method.run">', $html);
        self::assertStringContainsString('<pre class="member-sig"><code>', $html);
        self::assertStringContainsString('<span class="sig-name">run</span>', $html);
        self::assertStringContainsString('<a class="src-link" href="../../../src/src/Demo/Widget.php.html#L' . $method->startLine . '">source</a>', $html);
        self::assertStringContainsString('<a class="anchor" href="#method.run">§</a>', $html);
        self::assertStringContainsString('Called from <span class="count">1</span>', $html);
        self::assertStringContainsString('>Demo\Caller::boot()</a>', $html);
    }

    public function testMethodOmitsCallerSectionWithoutUsages(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public function run(): int
    {
        return 1;
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $pagePath = 'demo/pkg/Demo/class.Widget.html';
        $context = new TypeRenderContext($pagePath, 'Demo', [], [], [], $table);

        $html = (new MemberHtml())->method($services, $pagePath, $widget, $widget->methods[0], $context);

        self::assertStringNotContainsString('Called from', $html);
    }

    public function testPropertyRendersAnchorSignatureAndDocs(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    /**
     * Holds the current count.
     */
    public int $count = 0;
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $property = $widget->properties[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $pagePath = 'demo/pkg/Demo/class.Widget.html';
        $context = new TypeRenderContext($pagePath, 'Demo', [], [], [], $table);

        $html = (new MemberHtml())->property($services, $pagePath, $widget, $property, $context);

        self::assertStringContainsString('<div class="member" id="property.count">', $html);
        self::assertStringContainsString('<span class="t-var">$count</span> = <span class="t-lit">0</span>', $html);
        self::assertStringContainsString('#L' . $property->line . '">source</a>', $html);
        self::assertStringContainsString('<p class="lede">Holds the current count.</p>', $html);
    }

    public function testConstantRendersAnchorAndSignature(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public const LIMIT = 3;
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $constant = $widget->constants[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $pagePath = 'demo/pkg/Demo/class.Widget.html';
        $context = new TypeRenderContext($pagePath, 'Demo', [], [], [], $table);

        $html = (new MemberHtml())->constant($services, $pagePath, $widget, $constant, $context);

        self::assertStringContainsString('<div class="member" id="constant.LIMIT">', $html);
        self::assertStringContainsString('<span class="sig-name">LIMIT</span> = <span class="t-lit">3</span>', $html);
        self::assertStringContainsString('#L' . $constant->line . '">source</a>', $html);
    }

    public function testEnumCaseRendersAnchorAndSignature(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

enum Status: string
{
    case Active = 'active';
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Status.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Status.php', false);
        $status = $symbols->classLikes[0];
        $case = $status->enumCases[0];
        $table = new SymbolTable();
        $table->registerClassLike($status);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $pagePath = 'demo/pkg/Demo/enum.Status.html';
        $context = new TypeRenderContext($pagePath, 'Demo', [], [], [], $table);

        $html = (new MemberHtml())->enumCase($services, $pagePath, $status, $case, $context);

        self::assertStringContainsString('<div class="member" id="case.Active">', $html);
        self::assertStringContainsString('<span class="t-key">case</span> <span class="sig-name">Active</span> = <span class="t-lit">&#039;active&#039;</span>', $html);
        self::assertStringContainsString('#L' . $case->line . '">source</a>', $html);
    }

    public function testMetaRendersCoverageBadgeLevelsAndSourceLink(): void
    {
        $coverage = new CoverageIndex();
        $coverage->addMethod('src/Demo/Widget.php', 7, new MethodCoverage(20, 19, 95.0));
        $coverage->addMethod('src/Demo/Mid.php', 3, new MethodCoverage(10, 6, 60.0));
        $coverage->addMethod('src/Demo/Low.php', 3, new MethodCoverage(10, 1, 10.0));
        $table = new SymbolTable();
        $hierarchy = new HierarchyIndex();
        $hierarchy->build([]);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], [], $table, $hierarchy, $usages, new TestCaseIndex(), null, [], $coverage, []);
        $services = (new SiteRenderer())->services($model);
        $pagePath = 'demo/pkg/Demo/class.Widget.html';

        $html = (new MemberHtml())->meta($services, $pagePath, 'src/Demo/Widget.php', 7, 10, 'method.run');

        self::assertStringContainsString(
            '<span class="chip chip-sm chip-cov-high" title="19 of 20 executable lines executed by the test suite">95%</span>',
            $html,
        );
        self::assertStringContainsString('<a class="src-link" href="../../../src/src/Demo/Widget.php.html#L7">source</a><a class="anchor" href="#method.run">§</a>', $html);
        self::assertStringContainsString('chip-cov-mid', (new MemberHtml())->meta($services, $pagePath, 'src/Demo/Mid.php', 3, 5, 'm'));
        self::assertStringContainsString('chip-cov-low', (new MemberHtml())->meta($services, $pagePath, 'src/Demo/Low.php', 3, 5, 'm'));
        self::assertStringNotContainsString('chip-cov', (new MemberHtml())->meta($services, $pagePath, 'src/Demo/None.php', 1, 2, 'm'));
    }

    public function testParamTableRendersTheParameterAndReturnBlocksOfAMethod(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    /**
     * Runs the widget.
     *
     * @param int $count the run count
     * @param string $label
     */
    public function run(int $count, string $label): string
    {
        return $label . $count;
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        $html = (new MemberHtml())->paramTable($services, $widget->methods[0], $context);

        self::assertStringStartsWith('<div class="member-block"><h4>Parameters</h4><div class="table-wrap"><table class="param-table">', $html);
        self::assertStringContainsString('<tr><td><code class="t-var">$count</code></td><td><code><span class="t-key">int</span></code></td><td>the run count</td></tr>', $html);
        self::assertStringContainsString('<tr><td><code class="t-var">$label</code></td><td><code><span class="t-key">string</span></code></td><td></td></tr>', $html);
        self::assertStringEndsWith(
            '<div class="member-block"><h4>Returns</h4><div class="type-row"><code><span class="t-key">string</span></code></div></div>' . "\n",
            $html,
        );
    }

    public function testSignatureTableRendersFunctionParametersReturnAndThrows(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * Greets somebody.
 *
 * @param string $name the greeted name
 *
 * @throws \RuntimeException when the name is empty
 */
function greet(string $name): string
{
    return 'Hello ' . $name;
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/functions.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/functions.php', false);
        $function = $symbols->functions[0];
        $table = new SymbolTable();
        $table->registerFunction($function);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], $symbols->functions, $table, new HierarchyIndex(), $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/function.greet.html', 'Demo', [], [], [], $table);

        $html = (new MemberHtml())->signatureTable($services, $function->parameters, $function->returnType, $function->docBlock, $context);

        self::assertStringStartsWith('<div class="member-block"><h4>Parameters</h4>', $html);
        self::assertStringContainsString('<tr><td><code class="t-var">$name</code></td><td><code><span class="t-key">string</span></code></td><td>the greeted name</td></tr>', $html);
        self::assertStringContainsString(
            '<div class="member-block"><h4>Returns</h4><div class="type-row"><code><span class="t-key">string</span></code></div></div>',
            $html,
        );
        self::assertStringEndsWith(
            '<div class="member-block"><h4>Throws</h4><div class="type-row">'
            . '<code><span class="t-ext" title="RuntimeException">RuntimeException</span></code>'
            . ' <span class="type-note">when the name is empty</span></div></div>' . "\n",
            $html,
        );
    }

    public function testParameterSectionTabulatesEveryParameterOrRendersNothing(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    /**
     * Runs the widget.
     *
     * @param int $count the run count
     */
    public function run(int $count, string $label): string
    {
        return $label . $count;
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $usages = new UsageIndex();
        $usages->build([]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), $symbols->classLikes, [], $table, new HierarchyIndex(), $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        self::assertSame(
            '<div class="member-block"><h4>Parameters</h4><div class="table-wrap"><table class="param-table">'
            . '<tr><td><code class="t-var">$count</code></td><td><code><span class="t-key">int</span></code></td><td>the run count</td></tr>'
            . '<tr><td><code class="t-var">$label</code></td><td><code><span class="t-key">string</span></code></td><td></td></tr>'
            . '</table></div></div>' . "\n",
            (new MemberHtml())->parameterSection($services, $widget->methods[0]->parameters, $context),
        );
        self::assertSame('', (new MemberHtml())->parameterSection($services, [], $context));
    }

    public function testReturnSectionShowsTheTypeWithItsNoteAndSkipsVoid(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    /**
     * Runs the widget.
     *
     * @return int the run count
     */
    public function run(): int
    {
        return 1;
    }

    public function reset(): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $usages = new UsageIndex();
        $usages->build([]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), $symbols->classLikes, [], $table, new HierarchyIndex(), $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        self::assertSame(
            '<div class="member-block"><h4>Returns</h4><div class="type-row"><code><span class="t-key">int</span></code>'
            . ' <span class="type-note">the run count</span></div></div>' . "\n",
            (new MemberHtml())->returnSection($services, $widget->methods[0]->returnType, $context),
        );
        self::assertSame('', (new MemberHtml())->returnSection($services, $widget->methods[1]->returnType, $context));
        self::assertSame('', (new MemberHtml())->returnSection($services, new TypeSignature(null, null), $context));
    }

    public function testThrowsSectionListsOneTypeRowPerThrowsTag(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    /**
     * Runs the widget.
     *
     * @throws \RuntimeException when the count is negative
     * @throws \LogicException
     */
    public function run(): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $usages = new UsageIndex();
        $usages->build([]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), $symbols->classLikes, [], $table, new HierarchyIndex(), $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        self::assertSame(
            '<div class="member-block"><h4>Throws</h4>'
            . '<div class="type-row"><code><span class="t-ext" title="RuntimeException">RuntimeException</span></code>'
            . ' <span class="type-note">when the count is negative</span></div>'
            . '<div class="type-row"><code><span class="t-ext" title="LogicException">LogicException</span></code></div>'
            . '</div>' . "\n",
            (new MemberHtml())->throwsSection($services, $widget->methods[0]->docBlock, $context),
        );
        self::assertSame('', (new MemberHtml())->throwsSection($services, null, $context));
    }

    public function testBlockWrapsOneLabeledBodyOfTheMemberBody(): void
    {
        self::assertSame(
            '<div class="member-block"><h4>Parameters</h4><p>body</p></div>' . "\n",
            (new MemberHtml())->block('Parameters', '<p>body</p>'),
        );
    }

    public function testBlockEscapesTheLabelButKeepsTheBodyMarkup(): void
    {
        self::assertSame(
            '<div class="member-block"><h4>&lt;b&gt;Returns&lt;/b&gt; &amp; &quot;more&quot;</h4><p>body</p></div>' . "\n",
            (new MemberHtml())->block('<b>Returns</b> & "more"', '<p>body</p>'),
        );
    }

    public function testCallersKeepsProductionCallSitesAndDropsTestsAndNonCalls(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public function run(): int
    {
        return 1;
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([
            new Usage('Demo\Widget', 'run', 'method-call', 'Demo\Caller', 'boot', 'src/Demo/Caller.php', 12, false),
            new Usage('Demo\Widget', 'run', 'static-call', 'Demo\Other', 'boot', 'src/Demo/Other.php', 8, false),
            new Usage('Demo\Widget', 'run', 'class-const', 'Demo\Other', 'boot', 'src/Demo/Other.php', 9, false),
            new Usage('Demo\Widget', 'run', 'method-call', 'Tests\WidgetTest', 'testRun', 'tests/WidgetTest.php', 20, true),
        ]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $callers = (new MemberHtml())->callers($services, $widget, $widget->methods[0]);

        self::assertCount(2, $callers);
        self::assertSame('src/Demo/Caller.php', $callers[0]->file);
        self::assertSame('static-call', $callers[1]->kind);
    }

    public function testTagExamplesRendersRunnableAndInlineExamples(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    /**
     * Runs the widget.
     *
     * @example Doubling a count
     * $widget = new Widget();
     * $widget->run(2); // => 4
     * @example $widget->run(1)
     */
    public function run(int $count): int
    {
        return $count * 2;
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $html = (new MemberHtml())->tagExamples($services, $widget->methods[0]->docBlock);

        self::assertSame(2, substr_count($html, '<figure class="example">'));
        self::assertStringContainsString('<span class="example-title">Doubling a count</span>', $html);
        self::assertStringContainsString('<span class="doct doct-return">// =&gt; 4</span>', $html);
        self::assertStringContainsString('<span class="example-title">Example</span>', $html);
        self::assertSame(1, substr_count($html, 'chip-doctest'));
        self::assertSame('', (new MemberHtml())->tagExamples($services, null));
    }

    public function testMethodRendersTestCasesCalledFromAndCallsSections(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public function run(): int
    {
        return 1;
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $probe = new ClassLikeDoc('Tests\WidgetTest', 'WidgetTest', 'Tests', 'class', 'demo/pkg', 'tests/WidgetTest.php', 7, 30, false, true, [], [], [], [], [], [], [], null, null, [], true);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $table->registerClassLike($probe);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $references = [
            new Usage('Demo\Widget', 'run', 'method-call', 'Demo\Caller', 'boot', 'src/Demo/Caller.php', 12, false),
            new Usage('Demo\Widget', 'run', 'method-call', 'Tests\WidgetTest', 'testRun', 'tests/WidgetTest.php', 20, true),
            new Usage('Demo\Logger', 'log', 'method-call', 'Demo\Widget', 'run', 'src/Demo/Widget.php', 9, false),
        ];
        $usages = new UsageIndex();
        $usages->build($references);
        $testCases = new TestCaseIndex();
        $testCases->build($references, [$widget, $probe], null);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, $testCases, null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $pagePath = 'demo/pkg/Demo/class.Widget.html';
        $context = new TypeRenderContext($pagePath, 'Demo', [], [], [], $table);

        $html = (new MemberHtml())->method($services, $pagePath, $widget, $widget->methods[0], $context);

        self::assertStringContainsString('<details class="usage-details test-cases"><summary>Test cases <span class="count">1</span></summary>', $html);
        self::assertStringContainsString('<a href="../../../src/tests/WidgetTest.php.html#L20" title="Tests\WidgetTest"><code>WidgetTest::testRun</code></a>', $html);
        self::assertStringContainsString('<summary>Called from <span class="count">1</span></summary>', $html);
        self::assertStringContainsString('<code>Demo\Caller::boot()</code>', $html);
        self::assertStringNotContainsString('tests/WidgetTest.php:20', $html);
        self::assertStringContainsString('<summary>Calls <span class="count">1</span></summary>', $html);
        self::assertStringContainsString('<code>Logger::log()</code>', $html);
    }
}
