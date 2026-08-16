<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Signature;

use PhpAiToolkit\DocGen\Analysis\Doctest\AssertionScanner;
use PhpAiToolkit\DocGen\Analysis\Doctest\DoctestExtractor;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PhpAiToolkit\DocGen\Render\HtmlText;
use PhpAiToolkit\DocGen\Render\MarkdownRenderer;
use PhpAiToolkit\DocGen\Render\PhpHighlighter;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\Signature\SourceDigestIndex;
use PhpAiToolkit\DocGen\Render\Signature\SymbolReferenceScanner;
use PhpAiToolkit\DocGen\Render\SiteRenderer;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PhpAiToolkit\DocGen\Render\TypeHtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SymbolReferenceScanner::class)]
#[UsesClass(AssertionScanner::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DoctestExtractor::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(HtmlText::class)]
#[UsesClass(MarkdownRenderer::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(PhpHighlighter::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(RenderKit::class)]
#[UsesClass(SiteRenderer::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SourceDigestIndex::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeHtml::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
final class SymbolReferenceScannerTest extends TestCase
{
    public function testNamesListsEveryReadingOfEveryWrittenName(): void
    {
        $names = (new SymbolReferenceScanner())->names('Widget and Other\Thing', 'Demo', ['other' => 'Vendor\Other']);

        self::assertSame([
            'Widget',
            'and',
            'Other\Thing',
            'Demo\Widget',
            'Demo\and',
            'Demo\Other\Thing',
            'Vendor\Other\Thing',
        ], $names);
    }

    public function testNamesReadsEveryNameOutOfSerializedData(): void
    {
        $scanner = new SymbolReferenceScanner();

        self::assertContains('Demo\Widget', $scanner->names(serialize(new TypeSignature('Demo\Widget', null)), '', []));
        self::assertSame([], $scanner->names('', '', []));
    }

    public function testImportedResolvesAWrittenNameThroughTheImportsOfItsFile(): void
    {
        $scanner = new SymbolReferenceScanner();

        self::assertSame('Vendor\Other', $scanner->imported('Other', ['other' => 'Vendor\Other']));
        self::assertSame('Vendor\Other\Deep', $scanner->imported('Other\Deep', ['other' => 'Vendor\Other']));
        self::assertNull($scanner->imported('Widget', ['other' => 'Vendor\Other']));
    }

    public function testSymbolDigestNamesWhatEachNameResolvesTo(): void
    {
        $root = sys_get_temp_dir() . '/docgen-scanner-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Widget.php', '<?php class Widget {}');
        file_put_contents($root . '/src/fn.php', '<?php function greet() {}');
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $greet = new FunctionDoc('Demo\greet', 'greet', 'Demo', 'demo/pkg', 'src/fn.php', 1, 2, [], new TypeSignature(null, null), null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $table->registerFunction($greet);
        $model = new ProjectModel('T', $root, [], new PackageGraph([]), [$widget], [$greet], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $scanner = new SymbolReferenceScanner();
        $sources = new SourceDigestIndex();

        self::assertStringStartsWith('class-like|', $scanner->symbolDigest($services, $sources, 'Demo\Widget'));
        self::assertStringEndsWith('|demo/pkg|src', $scanner->symbolDigest($services, $sources, 'Demo\Widget'));
        self::assertStringStartsWith('function|', $scanner->symbolDigest($services, $sources, 'Demo\greet'));
        self::assertSame(SymbolReferenceScanner::UNRESOLVED, $scanner->symbolDigest($services, $sources, 'Demo\Absent'));
    }

    public function testResolvedReportsANameThatIsNoDocumentedSymbol(): void
    {
        $model = new ProjectModel('T', '/tmp/demo', [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);

        self::assertSame(
            SymbolReferenceScanner::UNRESOLVED,
            (new SymbolReferenceScanner())->resolved($services, new SourceDigestIndex(), 'Demo\\Widget'),
        );
    }

    public function testDigestFollowsTheFileOfEveryNamedSymbol(): void
    {
        $root = sys_get_temp_dir() . '/docgen-scanner-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Widget.php', '<?php class Widget {}');
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $table = new SymbolTable();
        $table->registerClassLike($widget);
        $model = new ProjectModel('T', $root, [], new PackageGraph([]), [$widget], [], $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $services = (new SiteRenderer())->services($model);
        $before = (new SymbolReferenceScanner())->digest($services, new SourceDigestIndex(), 'Demo\Widget is named here', '', []);

        self::assertSame($before, (new SymbolReferenceScanner())->digest($services, new SourceDigestIndex(), 'Demo\Widget is named here', '', []));

        file_put_contents($root . '/src/Widget.php', '<?php class Widget { public $added; }');

        self::assertNotSame($before, (new SymbolReferenceScanner())->digest($services, new SourceDigestIndex(), 'Demo\Widget is named here', '', []));
    }

    public function testDigestNoticesASymbolThatIsNotThereYet(): void
    {
        $root = sys_get_temp_dir() . '/docgen-scanner-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Widget.php', '<?php class Widget {}');
        $widget = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $empty = new SymbolTable();
        $filled = new SymbolTable();
        $filled->registerClassLike($widget);
        $without = new ProjectModel('T', $root, [], new PackageGraph([]), [], [], $empty, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $with = new ProjectModel('T', $root, [], new PackageGraph([]), [$widget], [], $filled, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);
        $renderer = new SiteRenderer();

        self::assertNotSame(
            (new SymbolReferenceScanner())->digest($renderer->services($without), new SourceDigestIndex(), 'Demo\Widget', '', []),
            (new SymbolReferenceScanner())->digest($renderer->services($with), new SourceDigestIndex(), 'Demo\Widget', '', []),
        );
    }
}
