<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Signature;

use PhpAiToolkit\DocGen\Analysis\Coverage\CoverageIndex;
use PhpAiToolkit\DocGen\Analysis\Coverage\MethodCoverage;
use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\MarkdownDoc;
use PhpAiToolkit\DocGen\Analysis\Model\MethodDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\Usage;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Cache\ToolkitFingerprint;
use PhpAiToolkit\DocGen\Package\ComposerManifest;
use PhpAiToolkit\DocGen\Package\DiscoveredPackage;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\Page\DocumentListHtml;
use PhpAiToolkit\DocGen\Render\Page\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\SidebarScope;
use PhpAiToolkit\DocGen\Render\Page\SymbolIndex;
use PhpAiToolkit\DocGen\Render\Page\SymbolRow;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\Signature\PageSignature;
use PhpAiToolkit\DocGen\Render\Signature\SidebarDigest;
use PhpAiToolkit\DocGen\Render\Signature\SourceDigestIndex;
use PhpAiToolkit\DocGen\Render\Signature\SymbolReferenceScanner;
use PhpAiToolkit\DocGen\Render\SiteRenderer;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PageSignature::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(CoverageIndex::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(DocumentListHtml::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownDoc::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(MethodCoverage::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SidebarDigest::class)]
#[UsesClass(SidebarHtml::class)]
#[UsesClass(SidebarScope::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SourceDigestIndex::class)]
#[UsesClass(SymbolIndex::class)]
#[UsesClass(SymbolReferenceScanner::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(ToolkitFingerprint::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(Usage::class)]
#[UsesClass(UsageIndex::class)]
final class PageSignatureTest extends TestCase
{
    public function testRunDigestsWhatEveryPageOfOneRunHasInCommon(): void
    {
        $renderer = new SiteRenderer();
        $model = new ProjectModel('Demo Docs', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renamed = new ProjectModel('Other Docs', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = $renderer->services($model);
        $signatures = new PageSignature();

        self::assertSame($signatures->run($services), $signatures->run($services));
        self::assertNotSame($signatures->run($services), $signatures->run($renderer->services($renamed)));
    }

    public function testOfDigestsThePartsAndTheNamesInThem(): void
    {
        $renderer = new SiteRenderer();
        $model = new ProjectModel('Demo Docs', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = $renderer->services($model);
        $signatures = new PageSignature();

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $signatures->of($services, ['a']));
        self::assertSame($signatures->of($services, ['a', 'b']), $signatures->of($services, ['a', 'b']));
        self::assertNotSame($signatures->of($services, ['a', 'b']), $signatures->of($services, ['a', 'c']));
    }

    public function testEveryKindOfPageHasASignatureOfItsOwn(): void
    {
        $root = sys_get_temp_dir() . '/docgen-signature-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Widget.php', '<?php class Widget {}');
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $greet = new FunctionDoc('Demo\greet', 'greet', 'Demo', 'demo/pkg', 'src/fn.php', 1, 2, [], new TypeSignature(null, null), null, [], false);
        $document = new MarkdownDoc('demo/pkg', 'docs/guide.md', 'docs/guide.md', 'Guide');
        $manifest = new ComposerManifest($root, 'demo/pkg', '', ['Demo\\' => ['src']], [], [], [], []);
        $package = new DiscoveredPackage($manifest, false);
        $model = new ProjectModel('T', $root, [$package], new PackageGraph([]), [$widget], [$greet], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, ['demo\widget' => ['Domain']], null, [], [$document]);
        $services = (new SiteRenderer())->services($model);
        $signatures = new PageSignature();

        $digests = [
            $signatures->index($services),
            $signatures->package($services, $package, '# Demo'),
            $signatures->allItems($services, 'demo/pkg'),
            $signatures->layer($services, 'demo/pkg', 'Domain'),
            $signatures->namespaced($services, 'demo/pkg', 'Demo'),
            $signatures->classLike($services, $widget),
            $signatures->functionPage($services, $greet),
            $signatures->source($services, 'src/Widget.php', '<?php class Widget {}', null),
            $signatures->document($services, $document, '# Guide', null),
        ];

        self::assertCount(9, array_unique($digests));
    }

    public function testIndexFollowsTheWarningsTheSiteShows(): void
    {
        $renderer = new SiteRenderer();
        $quiet = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $warned = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, ['Something could not be documented.']);
        $signatures = new PageSignature();

        self::assertNotSame($signatures->index($renderer->services($quiet)), $signatures->index($renderer->services($warned)));
    }

    public function testAllItemsFollowsTheSymbolsOfItsPackage(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $renderer = new SiteRenderer();
        $empty = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $filled = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);

        self::assertNotSame(
            (new PageSignature())->allItems($renderer->services($empty), 'demo/pkg'),
            (new PageSignature())->allItems($renderer->services($filled), 'demo/pkg'),
        );
    }

    public function testLayerFollowsTheSymbolsAssignedToIt(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $renderer = new SiteRenderer();
        $unassigned = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $assigned = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, ['demo\widget' => ['Domain']], null, []);

        self::assertNotSame(
            (new PageSignature())->layer($renderer->services($unassigned), 'demo/pkg', 'Domain'),
            (new PageSignature())->layer($renderer->services($assigned), 'demo/pkg', 'Domain'),
        );
    }

    public function testNamespacedFollowsTheSymbolsOfOneNamespace(): void
    {
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $engine = new ClassLikeDoc('Demo\Engine', 'Engine', 'Demo', 'class', 'demo/pkg', 'src/Engine.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $renderer = new SiteRenderer();
        $alone = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $paired = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [$widget, $engine], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);

        self::assertNotSame(
            (new PageSignature())->namespaced($renderer->services($alone), 'demo/pkg', 'Demo'),
            (new PageSignature())->namespaced($renderer->services($paired), 'demo/pkg', 'Demo'),
        );
    }

    public function testFunctionPageFollowsWhatCallsTheFunction(): void
    {
        $greet = new FunctionDoc('Demo\greet', 'greet', 'Demo', 'demo/pkg', 'src/fn.php', 1, 2, [], new TypeSignature(null, null), null, [], false);
        $renderer = new SiteRenderer();
        $uncalled = new UsageIndex();
        $called = new UsageIndex();
        $called->build([new Usage('Demo\greet', null, 'function-call', 'Demo\Widget', 'run', 'src/Widget.php', 9, false)]);
        $quiet = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [], [$greet], new SymbolTable(), new HierarchyIndex(), $uncalled, new TestCaseIndex(), null, [], null, []);
        $loud = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [], [$greet], new SymbolTable(), new HierarchyIndex(), $called, new TestCaseIndex(), null, [], null, []);

        self::assertNotSame(
            (new PageSignature())->functionPage($renderer->services($quiet), $greet),
            (new PageSignature())->functionPage($renderer->services($loud), $greet),
        );
    }

    public function testPackageFollowsTheReadmeItShows(): void
    {
        $root = sys_get_temp_dir() . '/docgen-signature-' . bin2hex(random_bytes(4));
        $document = new MarkdownDoc('demo/pkg', 'docs/guide.md', 'docs/guide.md', 'Guide');
        $manifest = new ComposerManifest($root, 'demo/pkg', '', ['Demo\\' => ['src']], [], [], [], []);
        $package = new DiscoveredPackage($manifest, false);
        $model = new ProjectModel('T', $root, [$package], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$document]);
        $services = (new SiteRenderer())->services($model);
        $signatures = new PageSignature();

        self::assertNotSame($signatures->package($services, $package, '# Demo'), $signatures->package($services, $package, '# Demo edited'));
    }

    public function testDocumentFollowsTheProseItShows(): void
    {
        $root = sys_get_temp_dir() . '/docgen-signature-' . bin2hex(random_bytes(4));
        $document = new MarkdownDoc('demo/pkg', 'docs/guide.md', 'docs/guide.md', 'Guide');
        $model = new ProjectModel('T', $root, [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$document]);
        $services = (new SiteRenderer())->services($model);
        $signatures = new PageSignature();

        self::assertNotSame(
            $signatures->document($services, $document, '# Guide', null),
            $signatures->document($services, $document, '# Guide edited', null),
        );
    }

    public function testSourceFollowsTheFileItShows(): void
    {
        $root = sys_get_temp_dir() . '/docgen-signature-' . bin2hex(random_bytes(4));
        $model = new ProjectModel('T', $root, [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $signatures = new PageSignature();

        self::assertNotSame(
            $signatures->source($services, 'src/A.php', '<?php', null),
            $signatures->source($services, 'src/A.php', '<?php echo 1;', null),
        );
        self::assertNotSame(
            $signatures->source($services, 'src/A.php', '<?php', null),
            $signatures->source($services, 'src/A.php', '<?php', '<?php echo 2;'),
        );
    }

    public function testClassLikeFollowsTheFilesOfTheSymbolsItNames(): void
    {
        $root = sys_get_temp_dir() . '/docgen-signature-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Widget.php', '<?php class Widget {}');
        file_put_contents($root . '/src/Engine.php', '<?php class Engine {}');
        $engine = new ClassLikeDoc('Demo\Engine', 'Engine', 'Demo', 'class', 'demo/pkg', 'src/Engine.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, ['Demo\Engine'], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $table->registerClassLike($engine);
        $manifest = new ComposerManifest($root, 'demo/pkg', '', ['Demo\\' => ['src']], [], [], [], []);
        $model = new ProjectModel('T', $root, [new DiscoveredPackage($manifest, false)], new PackageGraph([]), [$widget, $engine], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();
        $before = (new PageSignature())->classLike($renderer->services($model), $widget);

        self::assertSame($before, (new PageSignature())->classLike($renderer->services($model), $widget));

        file_put_contents($root . '/src/Engine.php', '<?php class Engine { public $added; }');

        self::assertNotSame($before, (new PageSignature())->classLike($renderer->services($model), $widget));
    }

    public function testMemberPartsCollectWhatTheRestOfTheProjectSaysAboutMembers(): void
    {
        $root = sys_get_temp_dir() . '/docgen-signature-' . bin2hex(random_bytes(4));
        $method = new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('void', null), null, 6, 7);
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 5, 8, false, true, [], [], [], [], [], [$method], [], null, null, [], false);
        $coverage = new CoverageIndex();
        $coverage->addMethod('src/Widget.php', 6, new MethodCoverage(2, 2, 100.0));
        $model = new ProjectModel('T', $root, [], new PackageGraph([]), [$widget], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], $coverage, []);
        $services = (new SiteRenderer())->services($model);
        $signatures = new PageSignature();

        $parts = $signatures->memberParts($services, $widget);

        self::assertCount(1, $parts);
        self::assertSame('run', $parts[0][0]);
        self::assertInstanceOf(MethodCoverage::class, $signatures->coverageOf($services, 'src/Widget.php', 6, 7));
        self::assertNull($signatures->coverageOf($services, 'src/Widget.php', 30, 40));
    }

    public function testCoverageOfIsNothingWhenNoReportWasLoaded(): void
    {
        $model = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        self::assertNull((new PageSignature())->coverageOf($services, 'src/Widget.php', 1, 2));
    }
}
