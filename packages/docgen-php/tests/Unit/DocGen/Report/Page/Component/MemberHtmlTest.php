<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report\Page\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Action\GenerateDocumentation;
use Toolkit\DocGen\Action\GenerationRequest;
use Toolkit\DocGen\Action\GenerationResult;
use Toolkit\DocGen\Analysis\AnalysisOptions;
use Toolkit\DocGen\Analysis\Coverage\CoverageIndex;
use Toolkit\DocGen\Analysis\Coverage\MethodCoverage;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCase as ReferenceTestCase;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Compare\DiffKey;
use Toolkit\DocGen\Compare\DiffStatus;
use Toolkit\DocGen\Compare\LineDiffer;
use Toolkit\DocGen\Discovery\Package\ComposerManifest;
use Toolkit\DocGen\Discovery\Package\DiscoveredPackage;
use Toolkit\DocGen\Discovery\SourceDiscovery;
use Toolkit\DocGen\Discovery\SourceFile;
use Toolkit\DocGen\Discovery\SourceSelection;
use Toolkit\DocGen\Discovery\SourceSet;
use Toolkit\DocGen\Parallel\WorkerCount;
use Toolkit\DocGen\Parallel\WorkerPool;
use Toolkit\DocGen\Parallel\WorkScheduler;
use Toolkit\DocGen\Parse\AstParser;
use Toolkit\DocGen\Parse\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Parse\Builder\ConstantBuilder;
use Toolkit\DocGen\Parse\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Parse\Builder\FunctionBuilder;
use Toolkit\DocGen\Parse\Builder\MethodBuilder;
use Toolkit\DocGen\Parse\Builder\ParameterBuilder;
use Toolkit\DocGen\Parse\Builder\PropertyBuilder;
use Toolkit\DocGen\Parse\Doc\DocBlockReader;
use Toolkit\DocGen\Parse\Doc\MutationContract;
use Toolkit\DocGen\Parse\Doc\MutationContractReader;
use Toolkit\DocGen\Parse\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Parse\ExprTextPrinter;
use Toolkit\DocGen\Parse\FileSymbolCollector;
use Toolkit\DocGen\Parse\NativeTypePrinter;
use Toolkit\DocGen\Parse\ParameterModifiers;
use Toolkit\DocGen\Parse\ParsedProject;
use Toolkit\DocGen\Parse\PhpParserBridge;
use Toolkit\DocGen\Parse\Reference\Usage;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\ClassLikeKind;
use Toolkit\DocGen\Parse\Symbol\ConstantDoc;
use Toolkit\DocGen\Parse\Symbol\DocBlock;
use Toolkit\DocGen\Parse\Symbol\DocTag;
use Toolkit\DocGen\Parse\Symbol\EnumCaseDoc;
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Parse\Symbol\FunctionDoc;
use Toolkit\DocGen\Parse\Symbol\MethodDoc;
use Toolkit\DocGen\Parse\Symbol\ParameterDoc;
use Toolkit\DocGen\Parse\Symbol\PropertyDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;
use Toolkit\DocGen\Parse\SymbolContext;
use Toolkit\DocGen\Parse\UseMapCollector;
use Toolkit\DocGen\Report\AssetPublisher;
use Toolkit\DocGen\Report\Diff\DiffHtml;
use Toolkit\DocGen\Report\Diff\MarkdownDiffHtml;
use Toolkit\DocGen\Report\Diff\SourceDiffHtml;
use Toolkit\DocGen\Report\Doctest\AssertionLine;
use Toolkit\DocGen\Report\Doctest\AssertionScanner;
use Toolkit\DocGen\Report\Doctest\DocExample;
use Toolkit\DocGen\Report\Doctest\DoctestExtractor;
use Toolkit\DocGen\Report\Filesystem\SiteFileWriter;
use Toolkit\DocGen\Report\HtmlText;
use Toolkit\DocGen\Report\MarkdownInline;
use Toolkit\DocGen\Report\MarkdownRenderer;
use Toolkit\DocGen\Report\Page\AllItemsPage;
use Toolkit\DocGen\Report\Page\ClassLikePage;
use Toolkit\DocGen\Report\Page\Component\BreadcrumbHtml;
use Toolkit\DocGen\Report\Page\Component\DocTextHtml;
use Toolkit\DocGen\Report\Page\Component\ExampleHtml;
use Toolkit\DocGen\Report\Page\Component\GraphSvg;
use Toolkit\DocGen\Report\Page\Component\MemberHtml;
use Toolkit\DocGen\Report\Page\Component\PrivateSurfaceHtml;
use Toolkit\DocGen\Report\Page\Component\RelationsHtml;
use Toolkit\DocGen\Report\Page\Component\SidebarHtml;
use Toolkit\DocGen\Report\Page\Component\SignatureHtml;
use Toolkit\DocGen\Report\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Report\Page\Component\TestCaseHtml;
use Toolkit\DocGen\Report\Page\Component\UsageListHtml;
use Toolkit\DocGen\Report\Page\DocumentPage;
use Toolkit\DocGen\Report\Page\FunctionPage;
use Toolkit\DocGen\Report\Page\IndexPage;
use Toolkit\DocGen\Report\Page\LayerPage;
use Toolkit\DocGen\Report\Page\NamespacePage;
use Toolkit\DocGen\Report\Page\PackagePage;
use Toolkit\DocGen\Report\Page\SourcePage;
use Toolkit\DocGen\Report\PageChrome;
use Toolkit\DocGen\Report\PhpHighlighter;
use Toolkit\DocGen\Report\RenderedSite;
use Toolkit\DocGen\Report\RenderKit;
use Toolkit\DocGen\Report\SearchIndexBuilder;
use Toolkit\DocGen\Report\Signature\PageSignature;
use Toolkit\DocGen\Report\Signature\SidebarDigest;
use Toolkit\DocGen\Report\SiteRenderer;
use Toolkit\DocGen\Report\SiteUrl;
use Toolkit\DocGen\Report\Social\SocialCard;
use Toolkit\DocGen\Report\Social\SocialMeta;
use Toolkit\DocGen\Report\TypeHtml;
use Toolkit\DocGen\Report\TypeRenderContext;

/**
 * @covers \Toolkit\DocGen\Report\Page\Component\MemberHtml
 * @uses \Toolkit\DocGen\Report\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionLine
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Report\AssetPublisher
 * @uses \Toolkit\DocGen\Parse\AstParser
 * @uses \Toolkit\DocGen\Report\Page\Component\BreadcrumbHtml
 * @uses \Toolkit\DocGen\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeKind
 * @uses \Toolkit\DocGen\Report\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ConstantDoc
 * @uses \Toolkit\DocGen\Analysis\Coverage\CoverageIndex
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Parse\Symbol\DocBlock
 * @uses \Toolkit\DocGen\Parse\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Report\Doctest\DocExample
 * @uses \Toolkit\DocGen\Parse\Symbol\DocTag
 * @uses \Toolkit\DocGen\Report\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Report\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Report\Page\DocumentPage
 * @uses \Toolkit\DocGen\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\EnumCaseDoc
 * @uses \Toolkit\DocGen\Report\Page\Component\ExampleHtml
 * @uses \Toolkit\DocGen\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Builder\FunctionBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\FunctionDoc
 * @uses \Toolkit\DocGen\Report\Page\FunctionPage
 * @uses \Toolkit\DocGen\Report\Page\Component\GraphSvg
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Report\HtmlText
 * @uses \Toolkit\DocGen\Report\Page\IndexPage
 * @uses \Toolkit\DocGen\Report\Page\LayerPage
 * @uses \Toolkit\DocGen\Compare\LineDiffer
 * @uses \Toolkit\DocGen\Report\Diff\MarkdownDiffHtml
 * @uses \Toolkit\DocGen\Report\MarkdownInline
 * @uses \Toolkit\DocGen\Report\MarkdownRenderer
 * @uses \Toolkit\DocGen\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Coverage\MethodCoverage
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Parse\Doc\MutationContract
 * @uses \Toolkit\DocGen\Parse\Doc\MutationContractReader
 * @uses \Toolkit\DocGen\Report\Page\NamespacePage
 * @uses \Toolkit\DocGen\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Report\Page\PackagePage
 * @uses \Toolkit\DocGen\Report\PageChrome
 * @uses \Toolkit\DocGen\Report\Signature\PageSignature
 * @uses \Toolkit\DocGen\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Report\PhpHighlighter
 * @uses \Toolkit\DocGen\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Report\Page\Component\PrivateSurfaceHtml
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\PropertyDoc
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCase
 * @uses \Toolkit\DocGen\Report\Page\Component\RelationsHtml
 * @uses \Toolkit\DocGen\Report\RenderKit
 * @uses \Toolkit\DocGen\Report\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Report\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Report\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Report\Page\Component\SignatureHtml
 * @uses \Toolkit\DocGen\Report\Filesystem\SiteFileWriter
 * @uses \Toolkit\DocGen\Report\SiteRenderer
 * @uses \Toolkit\DocGen\Report\SiteUrl
 * @uses \Toolkit\DocGen\Report\Social\SocialCard
 * @uses \Toolkit\DocGen\Report\Social\SocialMeta
 * @uses \Toolkit\DocGen\Report\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Report\Page\SourcePage
 * @uses \Toolkit\DocGen\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Report\Page\Component\TestCaseHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Report\TypeHtml
 * @uses \Toolkit\DocGen\Report\TypeRenderContext
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Parse\Reference\Usage
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Report\Page\Component\UsageListHtml
 * @uses \Toolkit\DocGen\Parse\UseMapCollector
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 * @uses \Toolkit\DocGen\Discovery\SourceSelection
 * @uses \Toolkit\DocGen\Discovery\SourceFile
 * @uses \Toolkit\DocGen\Discovery\SourceSet
 * @uses \Toolkit\DocGen\Discovery\SourceDiscovery
 * @uses \Toolkit\DocGen\Parse\ParsedProject
 * @uses \Toolkit\DocGen\Analysis\AnalysisOptions
 * @uses \Toolkit\DocGen\Analysis\ProjectAnalyzer
 * @uses \Toolkit\DocGen\Action\GenerateDocumentation
 * @uses \Toolkit\DocGen\Action\GenerationRequest
 * @uses \Toolkit\DocGen\Action\GenerationResult
 * @uses \Toolkit\DocGen\Report\RenderedSite
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
#[UsesClass(MutationContract::class)]
#[UsesClass(MutationContractReader::class)]
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
#[UsesClass(SourceSelection::class)]
#[UsesClass(SourceFile::class)]
#[UsesClass(SourceSet::class)]
#[UsesClass(SourceDiscovery::class)]
#[UsesClass(ParsedProject::class)]
#[UsesClass(AnalysisOptions::class)]
#[UsesClass(ProjectAnalyzer::class)]
#[UsesClass(GenerateDocumentation::class)]
#[UsesClass(GenerationRequest::class)]
#[UsesClass(GenerationResult::class)]
#[UsesClass(RenderedSite::class)]
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
        self::assertStringContainsString('<span class="signature-name">run</span>', $html);
        self::assertStringContainsString('<a class="source-link" href="../../../src/src/Demo/Widget.php.html#L' . $method->startLine . '">source</a>', $html);
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
        self::assertStringContainsString('<span class="signature-name">LIMIT</span> = <span class="t-lit">3</span>', $html);
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
        self::assertStringContainsString('<span class="t-key">case</span> <span class="signature-name">Active</span> = <span class="t-lit">&#039;active&#039;</span>', $html);
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
        self::assertStringContainsString('<a class="source-link" href="../../../src/src/Demo/Widget.php.html#L7">source</a><a class="anchor" href="#method.run">§</a>', $html);
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

    public function testMutationSectionRendersCompactCallableEffects(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    /**
     * @param object $value +mut changed value
     * @mutation $this, global
     */
    public function change(object $value): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $method = $symbols->classLikes[0]->methods[0];
        $table = new SymbolTable();
        $table->registerClassLike($symbols->classLikes[0]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), $symbols->classLikes, [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        $html = (new MemberHtml())->mutationSection(
            $services,
            $method->parameters,
            $method->docBlock,
        );

        self::assertStringContainsString('<h4>Mutates</h4>', $html);
        self::assertStringContainsString('<code>$value</code>', $html);
        self::assertStringContainsString('<code>$this</code>', $html);
        self::assertStringContainsString('<code>global</code>', $html);
        self::assertSame('changed value', $method->parameters[0]->description);
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

        self::assertSame(2, substr_count($html, '<figure class="example code-block">'));
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
