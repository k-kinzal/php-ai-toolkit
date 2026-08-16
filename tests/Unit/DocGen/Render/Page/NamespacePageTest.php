<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PhpAiToolkit\DocGen\Render\Diff\DiffModeControl;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownInline;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\Page\BreadcrumbHtml;
use PhpAiToolkit\DocGen\Render\Page\DocumentListHtml;
use PhpAiToolkit\DocGen\Render\Page\NamespacePage;
use PhpAiToolkit\DocGen\Render\Page\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\SidebarScope;
use PhpAiToolkit\DocGen\Render\Page\SymbolIndex;
use PhpAiToolkit\DocGen\Render\Page\SymbolListHtml;
use PhpAiToolkit\DocGen\Render\Page\SymbolRow;
use PhpAiToolkit\DocGen\Render\PageChrome;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NamespacePage::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(BreadcrumbHtml::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffModeControl::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentListHtml::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PageChrome::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolListHtml::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
final class NamespacePageTest extends TestCase
{
    public function testRenderProducesCompleteDocumentWithSidebarAndCrumbs(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, new DocBlock('Engine summary.', '', [], null, null, [], [], [], [], [], [], null, false, ''), [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/pkg', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, new DocBlock('Runner contract.', '', [], null, null, [], [], [], [], [], [], null, false, ''), [], false);
        $text = new ClassLikeDoc('Demo\Core\Util\Text', 'Text', 'Demo\Core\Util', 'class', 'demo/pkg', 'src/Core/Util/Text.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $make = new FunctionDoc('Demo\Core\make', 'make', 'Demo\Core', 'demo/pkg', 'src/Core/functions.php', 7, 10, [], new TypeSignature('int', null), null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $runner, $text], [$make], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new NamespacePage())->render($services, 'demo/pkg', 'Demo\Core');

        self::assertStringStartsWith('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<title>Demo\Core — Demo Docs</title>', $html);
        self::assertStringContainsString('<h1><span class="chip chip-kind k-namespace">namespace</span>Demo\Core</h1>', $html);
        self::assertStringContainsString(
            '<a href="../../../../demo/pkg/index.html">demo/pkg</a><span class="crumb-sep">::</span>'
            . '<a href="../../../../demo/pkg/Demo/index.html">Demo</a><span class="crumb-sep">::</span>'
            . '<span class="crumb-current">Core</span>',
            $html,
        );
        self::assertStringContainsString('<div class="sb-title">On this page</div>', $html);
        self::assertStringContainsString('<li><a href="#namespaces">Namespaces</a></li>', $html);
        self::assertStringContainsString('<li><a href="#interfaces">Interfaces</a></li>', $html);
        self::assertStringContainsString('<li><a href="#classes">Classes</a></li>', $html);
        self::assertStringContainsString('<li><a href="#functions">Functions</a></li>', $html);
        self::assertStringContainsString('<a href="../../../../demo/pkg/Demo/Core/index.html">In Demo\Core</a>', $html);
        self::assertStringContainsString('<li><a href="../../../../demo/pkg/all-items.html">All items</a></li>', $html);
    }

    public function testRenderOmitsNamespaceSectionWhenNoChildNamespaceExists(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $html = (new NamespacePage())->render($services, 'demo/pkg', 'Demo\Core');

        self::assertStringNotContainsString('id="namespaces"', $html);
        self::assertStringContainsString('id="classes"', $html);
    }

    public function testCrumbsBuildsOneCrumbPerNamespaceSegment(): void
    {
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $crumbs = (new NamespacePage())->crumbs($services, 'demo/pkg', 'Demo\Core\Util');

        self::assertSame([
            ['label' => 'demo/pkg', 'path' => 'demo/pkg/index.html'],
            ['label' => 'Demo', 'path' => 'demo/pkg/Demo/index.html'],
            ['label' => 'Core', 'path' => 'demo/pkg/Demo/Core/index.html'],
            ['label' => 'Util', 'path' => null],
        ], $crumbs);
    }

    public function testContentGroupsSymbolsByKindAfterChildNamespaces(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, new DocBlock('Engine summary.', '', [], null, null, [], [], [], [], [], [], null, false, ''), [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/pkg', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, new DocBlock('Runner contract.', '', [], null, null, [], [], [], [], [], [], null, false, ''), [], false);
        $text = new ClassLikeDoc('Demo\Core\Util\Text', 'Text', 'Demo\Core\Util', 'class', 'demo/pkg', 'src/Core/Util/Text.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $make = new FunctionDoc('Demo\Core\make', 'make', 'Demo\Core', 'demo/pkg', 'src/Core/functions.php', 7, 10, [], new TypeSignature('int', null), null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $runner, $text], [$make], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $pagePath = 'demo/pkg/Demo/Core/index.html';
        $rows = (new SymbolIndex())->inNamespace($services, 'demo/pkg', 'Demo\Core');

        $html = (new NamespacePage())->content($services, $pagePath, 'demo/pkg', 'Demo\Core', $rows);

        self::assertStringStartsWith('<div class="symbol-head"><h1><span class="chip chip-kind k-namespace">namespace</span>Demo\Core</h1></div>', $html);
        self::assertStringContainsString('<section class="items" id="namespaces"><h2>Namespaces <span class="count">1</span>', $html);
        self::assertStringContainsString(
            '<section class="items" id="interfaces"><h2>Interfaces <span class="count">1</span><a class="anchor" href="#interfaces">§</a></h2>',
            $html,
        );
        self::assertStringContainsString(
            '<tr><td><a class="item-name k-interface" href="../../../../demo/pkg/Demo/Core/interface.Runner.html">Runner</a></td>'
            . '<td class="item-summary">Runner contract.</td></tr>',
            $html,
        );
        self::assertStringContainsString(
            '<tr><td><a class="item-name k-class" href="../../../../demo/pkg/Demo/Core/class.Engine.html">Engine</a></td>'
            . '<td class="item-summary">Engine summary.</td></tr>',
            $html,
        );
        self::assertStringContainsString(
            '<tr><td><a class="item-name k-function" href="../../../../demo/pkg/Demo/Core/function.make.html">make</a></td>',
            $html,
        );
        self::assertStringContainsString("</table></div></section>\n<section class=\"items\" id=\"interfaces\">", $html);
        self::assertStringContainsString("</table></div></section>\n<section class=\"items\" id=\"classes\">", $html);
        self::assertStringContainsString("</table></div></section>\n<section class=\"items\" id=\"functions\">", $html);
    }

    public function testChildSectionLinksDirectChildNamespacesByLastSegment(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $text = new ClassLikeDoc('Demo\Core\Util\Text', 'Text', 'Demo\Core\Util', 'class', 'demo/pkg', 'src/Core/Util/Text.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $deep = new ClassLikeDoc('Demo\Core\Util\Case\Word', 'Word', 'Demo\Core\Util\Case', 'class', 'demo/pkg', 'src/Core/Util/Case/Word.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $text, $deep], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $pagePath = 'demo/pkg/Demo/Core/index.html';

        $html = (new NamespacePage())->childSection($services, $pagePath, 'demo/pkg', 'Demo\Core');

        self::assertStringContainsString(
            '<section class="items" id="namespaces"><h2>Namespaces <span class="count">1</span><a class="anchor" href="#namespaces">§</a></h2>'
            . '<div class="table-wrap"><table class="item-table">',
            $html,
        );
        self::assertStringContainsString(
            '<tr><td><a class="item-name k-namespace" href="../../../../demo/pkg/Demo/Core/Util/index.html">Util</a></td>'
            . '<td class="item-summary">Demo\Core\Util</td></tr>',
            $html,
        );
        self::assertStringNotContainsString('Word', $html);
    }

    public function testChildSectionRendersNothingForLeafNamespace(): void
    {
        $text = new ClassLikeDoc('Demo\Core\Util\Text', 'Text', 'Demo\Core\Util', 'class', 'demo/pkg', 'src/Core/Util/Text.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$text], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame('', (new NamespacePage())->childSection($services, 'demo/pkg/Demo/Core/Util/index.html', 'demo/pkg', 'Demo\Core\Util'));
    }
}
