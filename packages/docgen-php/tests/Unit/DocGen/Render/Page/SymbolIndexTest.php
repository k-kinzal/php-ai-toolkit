<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffIndex;
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
use Toolkit\DocGen\Render\HtmlText;
use Toolkit\DocGen\Render\MarkdownInline;
use Toolkit\DocGen\Render\MarkdownRenderer;
use Toolkit\DocGen\Render\Page\Component\SymbolRow;
use Toolkit\DocGen\Render\Page\SymbolIndex;
use Toolkit\DocGen\Render\PhpHighlighter;
use Toolkit\DocGen\Render\RenderKit;
use Toolkit\DocGen\Render\SiteUrl;
use Toolkit\DocGen\Render\TypeHtml;

/**
 * @covers \Toolkit\DocGen\Render\Page\SymbolIndex
 * @uses \Toolkit\DocGen\Analysis\Doctest\AssertionScanner
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Analysis\Model\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Doctest\DoctestExtractor
 * @uses \Toolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Render\HtmlText
 * @uses \Toolkit\DocGen\Render\MarkdownInline
 * @uses \Toolkit\DocGen\Render\MarkdownRenderer
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Render\RenderKit
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Render\Page\Component\SymbolRow
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Render\TypeHtml
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
#[CoversClass(SymbolIndex::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownInline::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SymbolRow::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
#[UsesClass(\Toolkit\Mutation\MutationContract::class)]
final class SymbolIndexTest extends TestCase
{
    public function testInNamespaceListsOwnSymbolsWithPageSummaryAndLayers(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, new DocBlock('Engine summary.', '', [], null, null, [], [], [], [], [], [], null, false, ''), [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/pkg', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $text = new ClassLikeDoc('Demo\Core\Util\Text', 'Text', 'Demo\Core\Util', 'class', 'demo/pkg', 'src/Core/Util/Text.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $probe = new ClassLikeDoc('Demo\Core\Probe', 'Probe', 'Demo\Core', 'class', 'demo/pkg', 'tests/Probe.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], true);
        $foreign = new ClassLikeDoc('Demo\Core\Alien', 'Alien', 'Demo\Core', 'class', 'other/pkg', 'src/Alien.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $make = new FunctionDoc('Demo\Core\make', 'make', 'Demo\Core', 'demo/pkg', 'src/Core/functions.php', 7, 10, [], new TypeSignature('int', null), new DocBlock('Makes a value.', '', [], null, null, [], [], [], [], [], [], null, false, ''), [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $runner, $text, $probe, $foreign], [$make], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, ['demo\core\engine' => ['Domain']], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $rows = (new SymbolIndex())->inNamespace($services, 'demo/pkg', 'Demo\Core');

        self::assertCount(3, $rows);
        self::assertSame('interface', $rows[0]->kind);
        self::assertSame('Runner', $rows[0]->name);
        self::assertSame('demo/pkg/Demo/Core/interface.Runner.html', $rows[0]->page);
        self::assertSame('', $rows[0]->summary);
        self::assertSame('Demo\Core', $rows[0]->namespace);
        self::assertSame('Engine', $rows[1]->name);
        self::assertSame('Engine summary.', $rows[1]->summary);
        self::assertSame(['Domain'], $rows[1]->layers);
        self::assertSame('function', $rows[2]->kind);
        self::assertSame('demo/pkg/Demo/Core/function.make.html', $rows[2]->page);
        self::assertSame('Makes a value.', $rows[2]->summary);
        self::assertSame([], $rows[2]->layers);
        self::assertSame('Demo\Core', $rows[2]->namespace);
    }

    public function testOpenRunDropsTheListingsOfTheRenderKitBefore(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/pkg', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $before = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $after = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $runner], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $index = new SymbolIndex();

        self::assertCount(1, $index->inPackage(new RenderKit($before, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner()), 'demo/pkg'));
        self::assertCount(2, $index->inPackage(new RenderKit($after, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner()), 'demo/pkg'));
    }

    public function testOpenPackageBuildsEveryNamespaceListingOnlyOnce(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $text = new ClassLikeDoc('Demo\Core\Util\Text', 'Text', 'Demo\Core\Util', 'class', 'demo/pkg', 'src/Core/Util/Text.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $text], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());
        $index = new SymbolIndex();

        $index->openPackage($services, 'demo/pkg');
        $index->openPackage($services, 'demo/pkg');

        self::assertSame(['Demo\Core', 'Demo\Core\Util'], $index->namespacesOf($services, 'demo/pkg'));
        self::assertCount(1, $index->inNamespace($services, 'demo/pkg', 'Demo\Core'));
        self::assertSame([], $index->inNamespace($services, 'demo/pkg', 'Demo\Missing'));
        self::assertSame([], $index->namespacesOf($services, 'other/pkg'));
    }

    public function testClassLikeRowsGroupsProductionClassLikesByNamespace(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $text = new ClassLikeDoc('Demo\Core\Util\Text', 'Text', 'Demo\Core\Util', 'class', 'demo/pkg', 'src/Core/Util/Text.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $probe = new ClassLikeDoc('Demo\Core\Probe', 'Probe', 'Demo\Core', 'class', 'demo/pkg', 'tests/Probe.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], true);
        $alien = new ClassLikeDoc('Demo\Core\Alien', 'Alien', 'Demo\Core', 'class', 'other/pkg', 'src/Alien.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $text, $probe, $alien], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, ['demo\core\engine' => ['Domain']], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $grouped = (new SymbolIndex())->classLikeRows($services, 'demo/pkg');

        self::assertSame(['Demo\Core', 'Demo\Core\Util'], array_keys($grouped));
        self::assertCount(1, $grouped['Demo\Core']);
        self::assertSame('Engine', $grouped['Demo\Core'][0]->name);
        self::assertSame(['Domain'], $grouped['Demo\Core'][0]->layers);
        self::assertSame('Text', $grouped['Demo\Core\Util'][0]->name);
        self::assertSame([], (new SymbolIndex())->classLikeRows($services, 'missing/pkg'));
    }

    public function testFunctionRowsGroupsProductionFunctionsByNamespace(): void
    {
        $make = new FunctionDoc('Demo\Api\make', 'make', 'Demo\Api', 'demo/pkg', 'src/Api/functions.php', 7, 10, [], new TypeSignature('int', null), new DocBlock('Makes a value.', '', [], null, null, [], [], [], [], [], [], null, false, ''), [], false);
        $probe = new FunctionDoc('Demo\Api\probe', 'probe', 'Demo\Api', 'demo/pkg', 'tests/functions.php', 7, 10, [], new TypeSignature('int', null), null, [], true);
        $alien = new FunctionDoc('Demo\Api\alien', 'alien', 'Demo\Api', 'other/pkg', 'src/functions.php', 7, 10, [], new TypeSignature('int', null), null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [], [$make, $probe, $alien], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $grouped = (new SymbolIndex())->functionRows($services, 'demo/pkg');

        self::assertSame(['Demo\Api'], array_keys($grouped));
        self::assertCount(1, $grouped['Demo\Api']);
        self::assertSame('function', $grouped['Demo\Api'][0]->kind);
        self::assertSame('make', $grouped['Demo\Api'][0]->name);
        self::assertSame('Makes a value.', $grouped['Demo\Api'][0]->summary);
        self::assertSame([], (new SymbolIndex())->functionRows($services, 'missing/pkg'));
    }

    public function testPublicApiModeListsOnlyExplicitPublicDeclarations(): void
    {
        $publicDoc = new DocBlock('Client API.', '', [], null, null, [], [], [], [], [], [], null, false, '', ['public']);
        $restrictedDoc = new DocBlock('Worker.', '', [], null, null, [], [], [], [], [], [], null, false, '', ['namespace']);
        $client = new ClassLikeDoc('Demo\Client', 'Client', 'Demo', 'class', 'demo/pkg', 'src/Client.php', 5, 20, false, true, [], [], [], [], [], [], [], null, $publicDoc, [], false);
        $worker = new ClassLikeDoc('Demo\Worker', 'Worker', 'Demo', 'class', 'demo/pkg', 'src/Worker.php', 5, 20, false, true, [], [], [], [], [], [], [], null, $restrictedDoc, [], false);
        $unmarked = new ClassLikeDoc('Demo\Helper', 'Helper', 'Demo', 'class', 'demo/pkg', 'src/Helper.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $create = new FunctionDoc('Demo\create', 'create', 'Demo', 'demo/pkg', 'src/functions.php', 7, 10, [], new TypeSignature('int', null), $publicDoc, [], false);
        $inspect = new FunctionDoc('Demo\inspect', 'inspect', 'Demo', 'demo/pkg', 'src/functions.php', 12, 15, [], new TypeSignature('int', null), null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$client, $worker, $unmarked], [$create, $inspect], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, null, true);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $rows = (new SymbolIndex())->inPackage($services, 'demo/pkg');

        self::assertSame(['Demo\Client', 'Demo\create'], [$rows[0]->fqcn, $rows[1]->fqcn]);
        self::assertSame(['public'], $rows[0]->visibility);
        self::assertSame(['Demo'], (new SymbolIndex())->namespacesOf($services, 'demo/pkg'));
    }

    public function testLayersOfListsAssignedLayersSortedAndUnique(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/pkg', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $assignments = ['demo\core\engine' => ['Infrastructure', 'Domain'], 'demo\core\runner' => ['Domain']];
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $runner], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, $assignments, null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(['Domain', 'Infrastructure'], (new SymbolIndex())->layersOf($services, 'demo/pkg'));
        self::assertSame([], (new SymbolIndex())->layersOf($services, 'other/pkg'));
    }

    public function testLayerStatusesCombineTheStateOfTheSymbolsOfEachLayer(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/pkg', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $assignments = ['demo\core\engine' => ['Infrastructure'], 'demo\core\runner' => ['Domain']];
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $runner], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, $assignments, null, []);
        $diff = new DiffIndex('main', 'HEAD');
        $diff->mark($diff->keys()->classLike('Demo\Core\Engine'), DiffStatus::ADDED);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner(), new DiffHtml($diff));

        self::assertSame(
            ['Domain' => DiffStatus::SAME, 'Infrastructure' => DiffStatus::ADDED],
            (new SymbolIndex())->layerStatuses($services, 'demo/pkg'),
        );
        self::assertSame([], (new SymbolIndex())->layerStatuses($services, 'other/pkg'));
    }

    public function testInPackageWalksEveryNamespaceOfThePackage(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $text = new ClassLikeDoc('Demo\Core\Util\Text', 'Text', 'Demo\Core\Util', 'class', 'demo/pkg', 'src/Core/Util/Text.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $text], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $rows = (new SymbolIndex())->inPackage($services, 'demo/pkg');

        self::assertCount(2, $rows);
        self::assertSame('Demo\Core\Engine', $rows[0]->fqcn);
        self::assertSame('Demo\Core\Util\Text', $rows[1]->fqcn);
        self::assertSame([], (new SymbolIndex())->inPackage($services, 'other/pkg'));
    }

    public function testInLayerKeepsOnlySymbolsAssignedToTheLayer(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $runner = new ClassLikeDoc('Demo\Core\Runner', 'Runner', 'Demo\Core', 'interface', 'demo/pkg', 'src/Core/Runner.php', 3, 9, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $assignments = ['demo\core\engine' => ['Domain', 'Infrastructure'], 'demo\core\runner' => ['Domain']];
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $runner], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, $assignments, null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        $domain = (new SymbolIndex())->inLayer($services, 'demo/pkg', 'Domain');
        $infrastructure = (new SymbolIndex())->inLayer($services, 'demo/pkg', 'Infrastructure');

        self::assertCount(2, $domain);
        self::assertCount(1, $infrastructure);
        self::assertSame('Engine', $infrastructure[0]->name);
        self::assertSame([], (new SymbolIndex())->inLayer($services, 'demo/pkg', 'Unknown'));
    }

    public function testByKindGroupsRowsInInterfaceFirstOrderAndSkipsEmptyKinds(): void
    {
        $rows = [
            new SymbolRow('class', 'Engine', 'Demo\Engine', 'demo/pkg/class.Engine.html', '', []),
            new SymbolRow('function', 'make', 'Demo\make', 'demo/pkg/function.make.html', '', []),
            new SymbolRow('interface', 'Runner', 'Demo\Runner', 'demo/pkg/interface.Runner.html', '', []),
        ];

        $groups = (new SymbolIndex())->byKind($rows);

        self::assertSame(['interface', 'class', 'function'], array_keys($groups));
        self::assertCount(1, $groups['interface']);
        self::assertSame('Runner', $groups['interface'][0]->name);
        self::assertSame('Engine', $groups['class'][0]->name);
        self::assertSame('make', $groups['function'][0]->name);
        self::assertSame([], (new SymbolIndex())->byKind([]));
    }

    public function testNamespacesOfListsProductionNamespacesSortedAndUnique(): void
    {
        $text = new ClassLikeDoc('Demo\Core\Util\Text', 'Text', 'Demo\Core\Util', 'class', 'demo/pkg', 'src/Core/Util/Text.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $probe = new ClassLikeDoc('Demo\Dev\Probe', 'Probe', 'Demo\Dev', 'class', 'demo/pkg', 'tests/Probe.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], true);
        $make = new FunctionDoc('Demo\Api\make', 'make', 'Demo\Api', 'demo/pkg', 'src/Api/functions.php', 7, 10, [], new TypeSignature('int', null), null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$text, $engine, $probe], [$make], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(['Demo\Api', 'Demo\Core', 'Demo\Core\Util'], (new SymbolIndex())->namespacesOf($services, 'demo/pkg'));
    }

    public function testChildNamespacesReturnsDirectChildrenOnly(): void
    {
        $engine = new ClassLikeDoc('Demo\Core\Engine', 'Engine', 'Demo\Core', 'class', 'demo/pkg', 'src/Core/Engine.php', 5, 20, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $word = new ClassLikeDoc('Demo\Core\Util\Text\Word', 'Word', 'Demo\Core\Util\Text', 'class', 'demo/pkg', 'src/Core/Util/Text/Word.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $global = new ClassLikeDoc('Loose', 'Loose', '', 'class', 'demo/pkg', 'src/Loose.php', 4, 11, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $package = new DiscoveredPackage(new ComposerManifest('/tmp/none', 'demo/pkg', 'Demo package', ['' => ['src']], [], [], [], []), false);
        $model = new ProjectModel('Demo Docs', '/tmp/none', [$package], new PackageGraph([]), [$engine, $word, $global], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = new RenderKit($model, new SiteUrl(), new HtmlText(), new PhpHighlighter(), new MarkdownRenderer(), new TypeHtml(), new DoctestExtractor(), new AssertionScanner());

        self::assertSame(['Demo\Core\Util'], (new SymbolIndex())->childNamespaces($services, 'demo/pkg', 'Demo\Core'));
        self::assertSame(['Demo'], (new SymbolIndex())->childNamespaces($services, 'demo/pkg', ''));
        self::assertSame([], (new SymbolIndex())->childNamespaces($services, 'demo/pkg', 'Demo\Core\Util\Text'));
    }

    public function testSortedOrdersByKindRankThenByName(): void
    {
        $rows = [
            new SymbolRow('function', 'zeta', 'Demo\zeta', 'demo/pkg/function.zeta.html', '', []),
            new SymbolRow('class', 'Zebra', 'Demo\Zebra', 'demo/pkg/class.Zebra.html', '', []),
            new SymbolRow('class', 'Alpha', 'Demo\Alpha', 'demo/pkg/class.Alpha.html', '', []),
            new SymbolRow('interface', 'Runner', 'Demo\Runner', 'demo/pkg/interface.Runner.html', '', []),
            new SymbolRow('enum', 'Color', 'Demo\Color', 'demo/pkg/enum.Color.html', '', []),
            new SymbolRow('trait', 'Loggable', 'Demo\Loggable', 'demo/pkg/trait.Loggable.html', '', []),
        ];

        $sorted = (new SymbolIndex())->sorted($rows);

        self::assertSame('Runner', $sorted[0]->name);
        self::assertSame('Alpha', $sorted[1]->name);
        self::assertSame('Zebra', $sorted[2]->name);
        self::assertSame('Loggable', $sorted[3]->name);
        self::assertSame('Color', $sorted[4]->name);
        self::assertSame('zeta', $sorted[5]->name);
    }
}
