<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageIndex;
use PhpAiToolkit\DocGen\Analysis\Coverage\MethodCoverage;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\LineDiffer;
use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind;
use PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc;
use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PhpAiToolkit\DocGen\Analysis\Model\DocTag;
use PhpAiToolkit\DocGen\Analysis\Model\EnumCaseDoc;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\MethodDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc;
use PhpAiToolkit\DocGen\Analysis\Model\PropertyDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\Parse\AstParser;
use PhpAiToolkit\DocGen\Analysis\Parse\ClassLikeBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ConstantBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\EnumCaseBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\FileSymbolCollector;
use PhpAiToolkit\DocGen\Analysis\Parse\FileSymbols;
use PhpAiToolkit\DocGen\Analysis\Parse\FunctionBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\MethodBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\ParameterBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ParameterModifiers;
use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\DocGen\Analysis\Parse\PropertyBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\SymbolContext;
use PhpAiToolkit\DocGen\Analysis\Parse\UseMapCollector;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCase as ReferenceTestCase;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\Usage;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Parallel\WorkerCount;
use PhpAiToolkit\DocGen\Parallel\WorkerPool;
use PhpAiToolkit\DocGen\Parallel\WorkScheduler;
use PhpAiToolkit\DocGen\Render\AssetPublisher;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PhpAiToolkit\DocGen\Render\Diff\MarkdownDiffHtml;
use PhpAiToolkit\DocGen\Render\Diff\SourceDiffHtml;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\Page\AllItemsPage;
use PhpAiToolkit\DocGen\Render\Page\BreadcrumbHtml;
use PhpAiToolkit\DocGen\Render\Page\ClassLikePage;
use PhpAiToolkit\DocGen\Render\Page\DocTextHtml;
use PhpAiToolkit\DocGen\Render\Page\DocumentPage;
use PhpAiToolkit\DocGen\Render\Page\ExampleHtml;
use PhpAiToolkit\DocGen\Render\Page\FunctionPage;
use PhpAiToolkit\DocGen\Render\Page\GraphSvg;
use PhpAiToolkit\DocGen\Render\Page\IndexPage;
use PhpAiToolkit\DocGen\Render\Page\LayerPage;
use PhpAiToolkit\DocGen\Render\Page\MemberHtml;
use PhpAiToolkit\DocGen\Render\Page\NamespacePage;
use PhpAiToolkit\DocGen\Render\Page\PackagePage;
use PhpAiToolkit\DocGen\Render\Page\PrivateSurfaceHtml;
use PhpAiToolkit\DocGen\Render\Page\RelationsHtml;
use PhpAiToolkit\DocGen\Render\Page\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\SignatureHtml;
use PhpAiToolkit\DocGen\Render\Page\SourcePage;
use PhpAiToolkit\DocGen\Render\Page\SymbolListHtml;
use PhpAiToolkit\DocGen\Render\Page\TestCaseHtml;
use PhpAiToolkit\DocGen\Render\Page\UsageListHtml;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\SearchIndexBuilder;
use PhpAiToolkit\DocGen\Render\Signature\PageSignature;
use PhpAiToolkit\DocGen\Render\Signature\SidebarDigest;
use PhpAiToolkit\DocGen\Render\SiteRenderer;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\SocialCard;
use PhpAiToolkit\DocGen\Render\SocialMeta;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PhpAiToolkit\DocGen\Render\TypeRenderContext;
use PhpAiToolkit\Doctest\Analysis\AssertionLine;
use PhpAiToolkit\Doctest\Analysis\AssertionScanner;
use PhpAiToolkit\Doctest\Analysis\DocExample;
use PhpAiToolkit\Doctest\Analysis\DoctestExtractor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
