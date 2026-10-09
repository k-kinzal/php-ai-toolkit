<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Report\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Action\GenerateDocumentation;
use Toolkit\DocGen\Action\GenerationRequest;
use Toolkit\DocGen\Action\GenerationResult;
use Toolkit\DocGen\Analysis\AnalysisOptions;
use Toolkit\DocGen\Analysis\Package\PackageGraph;
use Toolkit\DocGen\Analysis\ProjectAnalyzer;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCase as ReferenceTestCase;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Compare\DiffIndex;
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
use Toolkit\DocGen\Parse\Internal\AstParser;
use Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder;
use Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder;
use Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader;
use Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Parse\Internal\ExprTextPrinter;
use Toolkit\DocGen\Parse\Internal\FileSymbolCollector;
use Toolkit\DocGen\Parse\Internal\NativeTypePrinter;
use Toolkit\DocGen\Parse\Internal\ParameterModifiers;
use Toolkit\DocGen\Parse\Internal\PhpParserBridge;
use Toolkit\DocGen\Parse\Internal\SymbolContext;
use Toolkit\DocGen\Parse\Internal\UseMapCollector;
use Toolkit\DocGen\Parse\ParsedProject;
use Toolkit\DocGen\Parse\Reference\Usage;
use Toolkit\DocGen\Parse\Symbol\ClassLikeDoc;
use Toolkit\DocGen\Parse\Symbol\ClassLikeKind;
use Toolkit\DocGen\Parse\Symbol\ConstantDoc;
use Toolkit\DocGen\Parse\Symbol\DocBlock;
use Toolkit\DocGen\Parse\Symbol\DocTag;
use Toolkit\DocGen\Parse\Symbol\EnumCaseDoc;
use Toolkit\DocGen\Parse\Symbol\FileSymbols;
use Toolkit\DocGen\Parse\Symbol\MethodDoc;
use Toolkit\DocGen\Parse\Symbol\PropertyDoc;
use Toolkit\DocGen\Parse\Symbol\TemplateDoc;
use Toolkit\DocGen\Parse\Symbol\TypeAliasDoc;
use Toolkit\DocGen\Parse\Symbol\TypeSignature;
use Toolkit\DocGen\Report\AssetPublisher;
use Toolkit\DocGen\Report\Diff\DiffBanner;
use Toolkit\DocGen\Report\Diff\DiffHtml;
use Toolkit\DocGen\Report\Diff\DiffModeControl;
use Toolkit\DocGen\Report\Diff\MarkdownDiffHtml;
use Toolkit\DocGen\Report\Diff\SourceDiffHtml;
use Toolkit\DocGen\Report\Doctest\AssertionScanner;
use Toolkit\DocGen\Report\Doctest\DoctestExtractor;
use Toolkit\DocGen\Report\Filesystem\SiteFileWriter;
use Toolkit\DocGen\Report\HtmlText;
use Toolkit\DocGen\Report\MarkdownInline;
use Toolkit\DocGen\Report\MarkdownRenderer;
use Toolkit\DocGen\Report\Page\AllItemsPage;
use Toolkit\DocGen\Report\Page\ClassLikePage;
use Toolkit\DocGen\Report\Page\Component\BreadcrumbHtml;
use Toolkit\DocGen\Report\Page\Component\DocTextHtml;
use Toolkit\DocGen\Report\Page\Component\DocumentListHtml;
use Toolkit\DocGen\Report\Page\Component\ExampleHtml;
use Toolkit\DocGen\Report\Page\Component\GraphSvg;
use Toolkit\DocGen\Report\Page\Component\MemberHtml;
use Toolkit\DocGen\Report\Page\Component\PrivateSurfaceHtml;
use Toolkit\DocGen\Report\Page\Component\RelationsHtml;
use Toolkit\DocGen\Report\Page\Component\SidebarHtml;
use Toolkit\DocGen\Report\Page\Component\SignatureHtml;
use Toolkit\DocGen\Report\Page\Component\SymbolDescription;
use Toolkit\DocGen\Report\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Report\Page\Component\SymbolRow;
use Toolkit\DocGen\Report\Page\Component\TestCaseHtml;
use Toolkit\DocGen\Report\Page\Component\UsageListHtml;
use Toolkit\DocGen\Report\Page\DocumentPage;
use Toolkit\DocGen\Report\Page\FunctionPage;
use Toolkit\DocGen\Report\Page\IndexPage;
use Toolkit\DocGen\Report\Page\LayerPage;
use Toolkit\DocGen\Report\Page\NamespacePage;
use Toolkit\DocGen\Report\Page\PackagePage;
use Toolkit\DocGen\Report\Page\SidebarScope;
use Toolkit\DocGen\Report\Page\SourcePage;
use Toolkit\DocGen\Report\Page\SymbolIndex;
use Toolkit\DocGen\Report\PageChrome;
use Toolkit\DocGen\Report\PhpHighlighter;
use Toolkit\DocGen\Report\RenderedSite;
use Toolkit\DocGen\Report\RenderKit;
use Toolkit\DocGen\Report\RepositoryLink;
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
 * @covers \Toolkit\DocGen\Report\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Report\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Report\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Report\AssetPublisher
 * @uses \Toolkit\DocGen\Parse\Internal\AstParser
 * @uses \Toolkit\DocGen\Report\Page\Component\BreadcrumbHtml
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeDoc
 * @uses \Toolkit\DocGen\Parse\Symbol\ClassLikeKind
 * @uses \Toolkit\DocGen\Discovery\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\ConstantDoc
 * @uses \Toolkit\DocGen\Report\Diff\DiffBanner
 * @uses \Toolkit\DocGen\Report\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Compare\DiffIndex
 * @uses \Toolkit\DocGen\Compare\DiffKey
 * @uses \Toolkit\DocGen\Report\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Compare\DiffStatus
 * @uses \Toolkit\DocGen\Discovery\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Parse\Symbol\DocBlock
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Parse\Symbol\DocTag
 * @uses \Toolkit\DocGen\Report\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Report\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Report\Page\Component\DocumentListHtml
 * @uses \Toolkit\DocGen\Report\Page\DocumentPage
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\EnumCaseDoc
 * @uses \Toolkit\DocGen\Report\Page\Component\ExampleHtml
 * @uses \Toolkit\DocGen\Parse\Internal\ExprTextPrinter
 * @uses \Toolkit\DocGen\Parse\Internal\FileSymbolCollector
 * @uses \Toolkit\DocGen\Parse\Symbol\FileSymbols
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\FunctionBuilder
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
 * @uses \Toolkit\DocGen\Report\Page\Component\MemberHtml
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\MethodDoc
 * @uses \Toolkit\DocGen\Report\Page\NamespacePage
 * @uses \Toolkit\DocGen\Parse\Internal\NativeTypePrinter
 * @uses \Toolkit\DocGen\Analysis\Package\PackageGraph
 * @uses \Toolkit\DocGen\Report\Page\PackagePage
 * @uses \Toolkit\DocGen\Report\PageChrome
 * @uses \Toolkit\DocGen\Report\Signature\PageSignature
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Parse\Internal\ParameterModifiers
 * @uses \Toolkit\DocGen\Parse\Internal\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Report\PhpHighlighter
 * @uses \Toolkit\DocGen\Parse\Internal\PhpParserBridge
 * @uses \Toolkit\DocGen\Report\Page\Component\PrivateSurfaceHtml
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Parse\Internal\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Parse\Symbol\PropertyDoc
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCase
 * @uses \Toolkit\DocGen\Report\Page\Component\RelationsHtml
 * @uses \Toolkit\DocGen\Report\RenderKit
 * @uses \Toolkit\DocGen\Report\RepositoryLink
 * @uses \Toolkit\DocGen\Report\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Report\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Report\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Report\Page\SidebarScope
 * @uses \Toolkit\DocGen\Report\Page\Component\SignatureHtml
 * @uses \Toolkit\DocGen\Report\Filesystem\SiteFileWriter
 * @uses \Toolkit\DocGen\Report\SiteRenderer
 * @uses \Toolkit\DocGen\Report\SiteUrl
 * @uses \Toolkit\DocGen\Report\Social\SocialCard
 * @uses \Toolkit\DocGen\Report\Social\SocialMeta
 * @uses \Toolkit\DocGen\Report\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Report\Page\SourcePage
 * @uses \Toolkit\DocGen\Parse\Internal\SymbolContext
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolDescription
 * @uses \Toolkit\DocGen\Report\Page\SymbolIndex
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Report\Page\Component\SymbolRow
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Parse\Symbol\TemplateDoc
 * @uses \Toolkit\DocGen\Report\Page\Component\TestCaseHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeAliasDoc
 * @uses \Toolkit\DocGen\Report\TypeHtml
 * @uses \Toolkit\DocGen\Report\TypeRenderContext
 * @uses \Toolkit\DocGen\Parse\Symbol\TypeSignature
 * @uses \Toolkit\DocGen\Parse\Reference\Usage
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Report\Page\Component\UsageListHtml
 * @uses \Toolkit\DocGen\Parse\Internal\UseMapCollector
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
#[CoversClass(ClassLikePage::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikeKind::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(DiffBanner::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffModeControl::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocTag::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentListHtml::class)]
#[UsesClass(DocumentPage::class)]
#[UsesClass(EnumCaseBuilder::class)]
#[UsesClass(EnumCaseDoc::class)]
#[UsesClass(ExampleHtml::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(FileSymbolCollector::class)]
#[UsesClass(FileSymbols::class)]
#[UsesClass(FunctionBuilder::class)]
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
#[UsesClass(MemberHtml::class)]
#[UsesClass(MethodBuilder::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NamespacePage::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PackagePage::class)]
#[UsesClass(PageChrome::class)]
#[UsesClass(PageSignature::class)]
#[UsesClass(ParameterBuilder::class)]
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
#[UsesClass(RepositoryLink::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarDigest::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SignatureHtml::class)]
#[UsesClass(SiteFileWriter::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SourceDiffHtml::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolDescription::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TemplateDoc::class)]
#[UsesClass(TestCaseHtml::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeAliasDoc::class)]
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
#[UsesClass(\Toolkit\DocGen\Parse\Doc\MutationContract::class)]
#[UsesClass(\Toolkit\DocGen\Parse\Doc\MutationContractReader::class)]
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
final class ClassLikePageTest extends TestCase
{
    public function testRenderProducesCompleteDocument(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * Widget summary line.
 */
final class Widget
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

        $html = (new ClassLikePage())->render($services, $widget);

        self::assertStringStartsWith('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<title>Widget — Demo Docs</title>', $html);
        self::assertStringContainsString('<h1><span class="chip chip-kind k-class">class</span>Widget</h1>', $html);
        self::assertStringContainsString(
            '<a href="../../../demo/pkg/index.html">demo/pkg</a><span class="breadcrumb-sep">::</span>'
            . '<a href="../../../demo/pkg/Demo/index.html">Demo</a><span class="breadcrumb-sep">::</span>'
            . '<span class="breadcrumb-current">Widget</span>',
            $html,
        );
        self::assertStringContainsString('<p class="lede">Widget summary line.</p>', $html);
        self::assertStringContainsString('<h2 id="methods">', $html);
    }

    public function testContextBuildsTemplatesAliasesAndScope(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * Widget summary line.
 *
 * @template T of object
 * @phpstan-type Row array{id: int}
 */
final class Widget
{
}

class Plain
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $plain = $symbols->classLikes[1];
        $table = new SymbolTable();
        $table->registerClassLike($symbols->classLikes[0]);
        $table->registerClassLike($symbols->classLikes[1]);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $pagePath = 'demo/pkg/Demo/class.Widget.html';

        $context = (new ClassLikePage())->context($services, $pagePath, $widget, []);

        self::assertSame(['T'], $context->templates);
        self::assertSame(['Row' => '#alias.Row'], $context->aliases);
        self::assertSame($pagePath, $context->pagePath);
        self::assertSame('Demo', $context->namespace);

        $extra = $widget->docBlock !== null ? $widget->docBlock->templates : [];

        self::assertSame(['T'], (new ClassLikePage())->context($services, $pagePath, $plain, $extra)->templates);
    }

    public function testContentRendersHeadSignatureDocsAndLayerBadges(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * Widget summary line.
 */
final class Widget
{
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
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, ['demo\widget' => ['Domain']], null, []);
        $services = (new SiteRenderer())->services($model);
        $pagePath = 'demo/pkg/Demo/class.Widget.html';
        $context = new TypeRenderContext($pagePath, 'Demo', [], [], [], $table);

        $html = (new ClassLikePage())->content($services, $pagePath, $widget, $context);

        self::assertStringContainsString(
            '<a class="chip chip-layer" href="../../../demo/pkg/layer.Domain.html" title="deptrac layer">Domain</a>',
            $html,
        );
        self::assertStringContainsString(
            '<a class="source-link" href="../../../src/src/Demo/Widget.php.html#L' . $widget->startLine . '">src/Demo/Widget.php:' . $widget->startLine . '</a>',
            $html,
        );
        self::assertStringContainsString('<pre class="signature"><code>', $html);
        self::assertStringContainsString('<p class="lede">Widget summary line.</p>', $html);
    }

    public function testAliasSectionRendersTypeAliasDefinitions(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

/**
 * Widget summary line.
 *
 * @phpstan-type Row array{id: int}
 */
final class Widget
{
}

class Plain
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $plain = $symbols->classLikes[1];
        $table = new SymbolTable();
        $table->registerClassLike($symbols->classLikes[0]);
        $table->registerClassLike($symbols->classLikes[1]);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $pagePath = 'demo/pkg/Demo/class.Widget.html';
        $context = (new ClassLikePage())->context($services, $pagePath, $widget, []);

        $html = (new ClassLikePage())->aliasSection($services, $pagePath, $widget, $context);

        self::assertStringContainsString('<h2 id="aliases">Type Aliases<a class="anchor" href="#aliases">§</a></h2>', $html);
        self::assertStringContainsString(
            '<div class="member alias-def" id="alias.Row"><pre class="member-sig"><code><span class="t-alias">Row</span> = '
            . '<span class="t-key">array</span>{<span class="t-shape-key">id</span>: <span class="t-key">int</span>}</code></pre></div>',
            $html,
        );
        self::assertSame('', (new ClassLikePage())->aliasSection($services, $pagePath, $plain, $context));
    }

    public function testMemberSectionsRendersVisibleMembersAndPrivateSurface(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

final class Widget
{
    public const LIMIT = 3;

    private const SECRET = 'hidden';

    public int $count = 0;

    private string $token = '';

    public function run(): int
    {
        return $this->count;
    }

    private function seed(): void
    {
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

        $html = (new ClassLikePage())->memberSections($services, $pagePath, $widget, $context);

        self::assertStringContainsString('<h2 id="constants">Constants<a class="anchor" href="#constants">§</a></h2>', $html);
        self::assertStringContainsString('id="constant.LIMIT"', $html);
        self::assertStringContainsString('<h2 id="properties">Properties<a class="anchor" href="#properties">§</a></h2>', $html);
        self::assertStringContainsString('id="property.count"', $html);
        self::assertStringContainsString('<h2 id="methods">Methods<a class="anchor" href="#methods">§</a></h2>', $html);
        self::assertStringContainsString('id="method.run"', $html);
        self::assertStringNotContainsString('id="constant.SECRET"', $html);
        self::assertStringNotContainsString('id="property.token"', $html);
        self::assertStringNotContainsString('id="method.seed"', $html);
        self::assertStringNotContainsString('<h2 id="cases">', $html);
        self::assertStringNotContainsString('private-surface', $html);
    }

    public function testMemberSectionsRendersEnumCases(): void
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

        $html = (new ClassLikePage())->memberSections($services, $pagePath, $status, $context);

        self::assertStringContainsString('<h2 id="cases">Cases<a class="anchor" href="#cases">§</a></h2>', $html);
        self::assertStringContainsString('id="case.Active"', $html);
    }

    public function testMethodSectionRendersNonPrivateMethodsOnly(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

final class Widget
{
    public function run(): int
    {
        return 1;
    }

    private function seed(): void
    {
    }
}

class Plain
{
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $plain = $symbols->classLikes[1];
        $table = new SymbolTable();
        $table->registerClassLike($symbols->classLikes[0]);
        $table->registerClassLike($symbols->classLikes[1]);
        $hierarchy = new HierarchyIndex();
        $hierarchy->build($symbols->classLikes);
        $usages = new UsageIndex();
        $usages->build([]);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), $symbols->classLikes, $symbols->functions, $table, $hierarchy, $usages, new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $pagePath = 'demo/pkg/Demo/class.Widget.html';

        $html = (new ClassLikePage())->methodSection($services, $pagePath, $widget);

        self::assertStringStartsWith('<section><h2 id="methods">Methods<a class="anchor" href="#methods">§</a></h2>', $html);
        self::assertStringContainsString('id="method.run"', $html);
        self::assertStringNotContainsString('seed', $html);
        self::assertSame('', (new ClassLikePage())->methodSection($services, $pagePath, $plain));
    }

    public function testSectionsListsPresentSectionsInPageOrder(): void
    {
        $constants = [new ConstantDoc('LIMIT', 'public', '3', null, 6), new ConstantDoc('SECRET', 'private', "'x'", null, 8)];
        $properties = [new PropertyDoc('count', 'public', false, false, new TypeSignature('int', null), '0', null, 10)];
        $methods = [new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('int', null), null, 12, 15), new MethodDoc('seed', 'private', false, false, false, [], new TypeSignature('void', null), null, 17, 19)];
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 5, 20, false, true, [], [], [], $constants, $properties, $methods, [], null, null, [], false);
        $testCases = new TestCaseIndex();
        $testCases->record('Demo\Widget', null, new ReferenceTestCase('Tests\WidgetTest', 'testRun', null, null, ReferenceTestCase::ORIGIN_CALL));
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), $testCases, null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame([
            ['id' => 'constants', 'label' => 'Constants', 'status' => DiffStatus::SAME],
            ['id' => 'properties', 'label' => 'Properties', 'status' => DiffStatus::SAME],
            ['id' => 'methods', 'label' => 'Methods', 'status' => DiffStatus::SAME],
            ['id' => 'private-surface', 'label' => 'Private surface', 'status' => DiffStatus::SAME],
            ['id' => 'test-cases', 'label' => 'Test cases', 'status' => DiffStatus::SAME],
            ['id' => 'relations', 'label' => 'Relations', 'status' => DiffStatus::SAME],
        ], (new ClassLikePage())->sections($services, $widget));
    }

    public function testSectionsKeepsOnlyRelationsForABareSymbol(): void
    {
        $bare = new ClassLikeDoc('Demo\Bare', 'Bare', 'Demo', 'class', 'demo/pkg', 'src/Demo/Bare.php', 5, 8, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$bare], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(
            [['id' => 'relations', 'label' => 'Relations', 'status' => DiffStatus::SAME]],
            (new ClassLikePage())->sections($services, $bare),
        );
    }

    public function testVisibleMembersDropsPrivateMembers(): void
    {
        $members = [
            new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('int', null), null, 12, 15),
            new MethodDoc('boot', 'protected', false, false, false, [], new TypeSignature('void', null), null, 17, 19),
            new MethodDoc('seed', 'private', false, false, false, [], new TypeSignature('void', null), null, 21, 23),
        ];

        $visible = (new ClassLikePage())->visibleMembers($members);

        self::assertCount(2, $visible);
        self::assertSame('run', $visible[0]->name);
        self::assertSame('boot', $visible[1]->name);
        self::assertSame([], (new ClassLikePage())->visibleMembers([]));
    }

    public function testVisibleCasesDropsRestrictedCasesInPublicApiMode(): void
    {
        $restricted = new DocBlock('', '', [], null, null, [], [], [], [], [], [], null, false, '', ['parent']);
        $methods = [
            new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('int', null), null, 12, 15),
            new MethodDoc('inspect', 'public', false, false, false, [], new TypeSignature('void', null), $restricted, 17, 19),
        ];
        $cases = [new EnumCaseDoc('READY', null, null, 22), new EnumCaseDoc('INTERNAL', null, $restricted, 23)];
        $page = new ClassLikePage();

        self::assertSame('run', $page->visibleMembers($methods, true)[0]->name);
        self::assertCount(1, $page->visibleMembers($methods, true));
        self::assertSame('READY', $page->visibleCases($cases, true)[0]->name);
        self::assertCount(1, $page->visibleCases($cases, true));
        self::assertCount(2, $page->visibleMembers($methods));
        self::assertCount(2, $page->visibleCases($cases));
    }

    public function testTestCaseSectionSplitsDedicatedTestsFromOtherTests(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $bare = new ClassLikeDoc('Demo\Bare', 'Bare', 'Demo', 'class', 'demo/pkg', 'src/Demo/Bare.php', 5, 8, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $testCases = new TestCaseIndex();
        $testCases->build([
            new Usage('Demo\Widget', 'run', 'method-call', 'Tests\WidgetTest', 'testRun', 'tests/WidgetTest.php', 20, true),
            new Usage('Demo\Widget', 'run', 'method-call', 'Tests\AppTest', 'testBoot', 'tests/AppTest.php', 33, true),
        ], [$widget], null);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), $testCases, null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new ClassLikePage())->testCaseSection($services, 'demo/pkg/Demo/class.Widget.html', $widget);

        self::assertStringStartsWith(
            '<section><h2 id="test-cases">Test cases <span class="count">2</span><a class="anchor" href="#test-cases">§</a></h2>'
            . '<p class="section-note">Test cases that cover or call this symbol, from the coverage report and from the analyzed test sources.</p>'
            . '<details class="usage-details test-cases" open><summary>Dedicated tests <span class="count">1</span></summary><ul class="usage-list">'
            . '<li><a href="../../../src/tests/WidgetTest.php.html#L20" title="Tests\WidgetTest"><code>WidgetTest::testRun</code></a>',
            $html,
        );
        self::assertStringContainsString(
            '<details class="usage-details test-cases"><summary>Other tests reaching this symbol <span class="count">1</span></summary><ul class="usage-list">'
            . '<li><a href="../../../src/tests/AppTest.php.html#L33" title="Tests\AppTest"><code>AppTest::testBoot</code></a>',
            $html,
        );
        self::assertStringEndsWith("</section>\n", $html);
        self::assertSame('', (new ClassLikePage())->testCaseSection($services, 'demo/pkg/Demo/class.Bare.html', $bare));
    }

    public function testIsDedicatedTestMatchesOnlyTheShortNameTestClass(): void
    {
        self::assertTrue((new ClassLikePage())->isDedicatedTest('Tests\Unit\Demo\WidgetTest', 'Widget'));
        self::assertTrue((new ClassLikePage())->isDedicatedTest('WidgetTest', 'Widget'));
        self::assertFalse((new ClassLikePage())->isDedicatedTest('Tests\Unit\Demo\AppTest', 'Widget'));
        self::assertFalse((new ClassLikePage())->isDedicatedTest('Tests\Unit\Demo\WidgetFactoryTest', 'Widget'));
    }

    public function testSectionStatusCombinesTheStatesOfTheMembersOfOneSection(): void
    {
        $methods = [
            new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('int', null), null, 12, 15),
            new MethodDoc('stop', 'public', false, false, false, [], new TypeSignature('void', null), null, 17, 19),
        ];
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 5, 20, false, true, [], [], [], [], [], $methods, [], null, null, [], false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->member('Demo\Widget', DiffKey::METHOD, 'run'), DiffStatus::ADDED);
        $index->mark($index->keys()->member('Demo\Widget', DiffKey::METHOD, 'stop'), DiffStatus::ADDED);
        $page = new ClassLikePage();

        self::assertSame(
            DiffStatus::ADDED,
            $page->sectionStatus((new SiteRenderer())->services($model, $index), $widget, DiffKey::METHOD, $methods),
        );
        self::assertSame(
            DiffStatus::SAME,
            $page->sectionStatus((new SiteRenderer())->services($model, $index), $widget, DiffKey::CONSTANT, []),
        );
    }

    public function testSectionMarkCombinesTheStatesOfTheMembersOfOneSection(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    public function run(): void
    {
    }

    public function stop(): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $widget = $symbols->classLikes[0];
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->member('Demo\Widget', DiffKey::METHOD, 'run'), DiffStatus::ADDED);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), $symbols->classLikes, [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $page = new ClassLikePage();

        self::assertSame(
            ' data-diff="modified"',
            $page->sectionMark((new SiteRenderer())->services($model, $index), $widget, DiffKey::METHOD, $widget->methods),
        );
        self::assertSame(
            ' data-diff="same"',
            $page->sectionMark((new SiteRenderer())->services($model, $index), $widget, DiffKey::CONSTANT, []),
        );
        self::assertSame('', $page->sectionMark((new SiteRenderer())->services($model), $widget, DiffKey::METHOD, $widget->methods));
    }
}
