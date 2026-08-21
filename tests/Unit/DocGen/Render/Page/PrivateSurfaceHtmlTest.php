<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\LineDiffer;
use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc;
use PhpAiToolkit\DocGen\Analysis\Model\MethodDoc;
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
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
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
use PhpAiToolkit\DocGen\Render\Page\ClassLikePage;
use PhpAiToolkit\DocGen\Render\Page\DocTextHtml;
use PhpAiToolkit\DocGen\Render\Page\DocumentPage;
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
use PhpAiToolkit\Doctest\Analysis\AssertionScanner;
use PhpAiToolkit\Doctest\Analysis\DoctestExtractor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
