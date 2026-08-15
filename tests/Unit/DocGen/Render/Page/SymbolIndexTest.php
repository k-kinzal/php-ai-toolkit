<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

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
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\Page\SymbolIndex;
use PhpAiToolkit\DocGen\Render\Page\SymbolRow;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SymbolIndex::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
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
