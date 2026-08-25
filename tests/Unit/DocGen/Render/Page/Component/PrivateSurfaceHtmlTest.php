<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page\Component;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffIndex;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Diff\LineDiffer;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Analysis\Doctest\AssertionScanner;
use Toolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\ConstantDoc;
use Toolkit\DocGen\Analysis\Model\MethodDoc;
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
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
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
use Toolkit\DocGen\Render\Page\Component\DocTextHtml;
use Toolkit\DocGen\Render\Page\Component\GraphSvg;
use Toolkit\DocGen\Render\Page\Component\MemberHtml;
use Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml;
use Toolkit\DocGen\Render\Page\Component\RelationsHtml;
use Toolkit\DocGen\Render\Page\Component\SidebarHtml;
use Toolkit\DocGen\Render\Page\Component\SignatureHtml;
use Toolkit\DocGen\Render\Page\Component\SymbolListHtml;
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
 * @covers \Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml
 * @uses \Toolkit\DocGen\Render\Page\AllItemsPage
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\AssetPublisher
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Render\Page\ClassLikePage
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ConstantDoc
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Render\Page\Component\DocTextHtml
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Render\Page\DocumentPage
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbolCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\FileSymbols
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder
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
 * @uses \Toolkit\DocGen\Render\Page\Component\MemberHtml
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \Toolkit\DocGen\Render\Page\NamespacePage
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Render\Page\PackagePage
 * @uses \Toolkit\DocGen\Render\PageChrome
 * @uses \Toolkit\DocGen\Render\Signature\PageSignature
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Render\PhpHighlighter
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\PropertyDoc
 * @uses \Toolkit\DocGen\Render\Page\Component\RelationsHtml
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Render\Signature\SidebarDigest
 * @uses \Toolkit\DocGen\Render\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Render\Page\Component\SignatureHtml
 * @uses \Toolkit\DocGen\Render\SiteRenderer
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Render\Social\SocialCard
 * @uses \Toolkit\DocGen\Render\Social\SocialMeta
 * @uses \Toolkit\DocGen\Render\Diff\SourceDiffHtml
 * @uses \Toolkit\DocGen\Render\Page\SourcePage
 * @uses \Toolkit\DocGen\Analysis\Parse\SymbolContext
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Render\TypeRenderContext
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 * @uses \Toolkit\DocGen\Analysis\Parse\UseMapCollector
 * @uses \Toolkit\DocGen\Parallel\WorkScheduler
 * @uses \Toolkit\DocGen\Parallel\WorkerCount
 * @uses \Toolkit\DocGen\Parallel\WorkerPool
 */
#[CoversClass(PrivateSurfaceHtml::class)]
#[UsesClass(AllItemsPage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(AssetPublisher::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(ClassLikeBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ClassLikePage::class)]
#[UsesClass(ConstantBuilder::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(DocTextHtml::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentPage::class)]
#[UsesClass(EnumCaseBuilder::class)]
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
#[UsesClass(ProjectModel::class)]
#[UsesClass(PropertyBuilder::class)]
#[UsesClass(PropertyDoc::class)]
#[UsesClass(RelationsHtml::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SearchIndexBuilder::class)]
#[UsesClass(SidebarDigest::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SignatureHtml::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
#[UsesClass(SourceDiffHtml::class)]
#[UsesClass(SourcePage::class)]
#[UsesClass(SymbolContext::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeRenderContext::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(UseMapCollector::class)]
#[UsesClass(WorkScheduler::class)]
#[UsesClass(WorkerCount::class)]
#[UsesClass(WorkerPool::class)]
final class PrivateSurfaceHtmlTest extends TestCase
{
    public function testMembersCollectsConstantsPropertiesAndMethodsInThatOrder(): void
    {
        $constants = [new ConstantDoc('LIMIT', 'public', '3', null, 6), new ConstantDoc('SECRET', 'private', "'x'", null, 8)];
        $properties = [new PropertyDoc('token', 'private', false, false, new TypeSignature('string', null), "''", null, 10)];
        $methods = [new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('int', null), null, 12, 15), new MethodDoc('seed', 'private', false, false, false, [], new TypeSignature('void', null), null, 17, 19)];
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 5, 20, false, true, [], [], [], $constants, $properties, $methods, [], null, null, [], false);

        $private = (new PrivateSurfaceHtml())->members($widget);

        self::assertCount(3, $private);
        self::assertSame('SECRET', $private[0]->name);
        self::assertSame('token', $private[1]->name);
        self::assertSame('seed', $private[2]->name);
    }

    public function testStatusesListTheStateOfEveryPrivateMemberInOrder(): void
    {
        $constants = [new ConstantDoc('LIMIT', 'public', '3', null, 6), new ConstantDoc('SECRET', 'private', "'x'", null, 8)];
        $properties = [new PropertyDoc('token', 'private', false, false, new TypeSignature('string', null), "''", null, 10)];
        $methods = [new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('int', null), null, 12, 15), new MethodDoc('seed', 'private', false, false, false, [], new TypeSignature('void', null), null, 17, 19)];
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Demo/Widget.php', 5, 20, false, true, [], [], [], $constants, $properties, $methods, [], null, null, [], false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->member('Demo\Widget', DiffKey::METHOD, 'seed'), DiffStatus::ADDED);

        self::assertSame(
            [DiffStatus::SAME, DiffStatus::SAME, DiffStatus::ADDED],
            (new PrivateSurfaceHtml())->statuses((new SiteRenderer())->services($model, $index), $widget),
        );
    }

    public function testSectionListsOnlyThePrivateMembers(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

final class Widget
{
    public const LIMIT = 3;

    private const SECRET = 'hidden';

    private string $token = '';

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
        $table = new SymbolTable();
        $table->registerClassLike($symbols->classLikes[0]);
        $table->registerClassLike($symbols->classLikes[1]);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), $symbols->classLikes, [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        $html = (new PrivateSurfaceHtml())->section($services, $symbols->classLikes[0], $context);

        self::assertStringStartsWith(
            '<section class="private-surface"><h2 id="private-surface">Private surface <span class="count">3</span>'
            . '<a class="anchor" href="#private-surface">§</a></h2>'
            . '<p class="section-note">Implementation details, listed for orientation only.</p>'
            . '<pre class="member-sig private-sig"><code>',
            $html,
        );
        self::assertStringContainsString('<span class="sig-name">SECRET</span>', $html);
        self::assertStringContainsString('<span class="t-var">$token</span>', $html);
        self::assertStringContainsString('<span class="sig-name">seed</span>', $html);
        self::assertStringNotContainsString('<span class="sig-name">LIMIT</span>', $html);
        self::assertSame('', (new PrivateSurfaceHtml())->section($services, $symbols->classLikes[1], $context));
    }

    public function testSectionCarriesTheCombinedStateOfTheComparedMembers(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    private function seed(): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $table = new SymbolTable();
        $table->registerClassLike($symbols->classLikes[0]);
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->member('Demo\Widget', DiffKey::METHOD, 'seed'), DiffStatus::ADDED);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), $symbols->classLikes, [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model, $index);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        $html = (new PrivateSurfaceHtml())->section($services, $symbols->classLikes[0], $context);

        self::assertStringStartsWith('<section class="private-surface" data-diff="added">', $html);
        self::assertStringContainsString('<pre class="member-sig private-sig" data-diff="added">', $html);
    }

    public function testRowsRenderEveryPrivateMemberWithItsState(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Widget
{
    private const SECRET = 1;

    private string $token = 'x';

    private function seed(): void
    {
    }
}
PHP;
        $statements = (new AstParser())->parse($code, 'src/Demo/Widget.php');
        $symbols = (new FileSymbolCollector())->collect($statements, 'demo/pkg', 'src/Demo/Widget.php', false);
        $table = new SymbolTable();
        $table->registerClassLike($symbols->classLikes[0]);
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->member('Demo\Widget', DiffKey::METHOD, 'seed'), DiffStatus::ADDED);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [], new PackageGraph([]), $symbols->classLikes, [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model, $index);
        $context = new TypeRenderContext('demo/pkg/Demo/class.Widget.html', 'Demo', [], [], [], $table);

        $rows = (new PrivateSurfaceHtml())->rows($services, $symbols->classLikes[0], $context);

        self::assertCount(3, $rows);
        self::assertStringContainsString('<span class="sig-name">SECRET</span>', $rows[0]['html']);
        self::assertSame(DiffStatus::SAME, $rows[0]['status']);
        self::assertStringContainsString('<span class="t-var">$token</span>', $rows[1]['html']);
        self::assertStringContainsString('<span class="sig-name">seed</span>', $rows[2]['html']);
        self::assertSame(DiffStatus::ADDED, $rows[2]['status']);
    }
}
