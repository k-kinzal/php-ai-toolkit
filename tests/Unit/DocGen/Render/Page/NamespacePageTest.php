<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Doctest\AssertionScanner;
use Toolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Package\ComposerManifest;
use Toolkit\DocGen\Package\DiscoveredPackage;
use Toolkit\DocGen\Package\PackageGraph;
use Toolkit\DocGen\Render\Diff\DiffHtml;
use Toolkit\DocGen\Render\Diff\DiffModeControl;
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\MarkdownInline;
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\Page\Component\BreadcrumbHtml;
use Toolkit\DocGen\Render\Page\Component\DocumentListHtml;
use Toolkit\DocGen\Render\Page\Component\SidebarHtml;
use Toolkit\DocGen\Render\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Render\Page\Component\SymbolRow;
use Toolkit\DocGen\Render\Page\NamespacePage;
use Toolkit\DocGen\Render\Page\SidebarScope;
use Toolkit\DocGen\Render\Page\SymbolIndex;
use Toolkit\DocGen\Render\PageChrome;
use Toolkit\DocGen\Render\PhpHighlighter;
use Toolkit\DocGen\Render\RenderKit;
use Toolkit\DocGen\Render\RepositoryLink;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\Social\SocialCard;
use Toolkit\DocGen\Render\Social\SocialMeta;
use Toolkit\DocGen\Render\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Render\Page\NamespacePage
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Render\Page\Component\BreadcrumbHtml
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Render\Diff\DiffModeControl
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Analysis\Model\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Render\Page\Component\DocumentListHtml
 * @uses \Toolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Render\MarkdownInline
 * @uses \Toolkit\DocGen\Render\MarkdownRenderer
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Render\PageChrome
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\RepositoryLink
 * @uses \Toolkit\DocGen\Render\Page\Component\SidebarHtml
 * @uses \Toolkit\DocGen\Render\Page\SidebarScope
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Render\Social\SocialCard
 * @uses \Toolkit\DocGen\Render\Social\SocialMeta
 * @uses \Toolkit\DocGen\Render\Page\SymbolIndex
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolListHtml
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolRow
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
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
#[UsesClass(RepositoryLink::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SocialCard::class)]
#[UsesClass(SocialMeta::class)]
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
