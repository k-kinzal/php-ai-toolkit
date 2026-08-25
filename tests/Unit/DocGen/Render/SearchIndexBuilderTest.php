<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc;
use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\MethodDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PhpAiToolkit\DocGen\Render\SearchIndexBuilder;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Render\SearchIndexBuilder
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc
 * @uses \PhpAiToolkit\DocGen\Render\Diff\DiffHtml
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\DocBlock
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \PhpAiToolkit\DocGen\Package\PackageGraph
 * @uses \PhpAiToolkit\DocGen\Analysis\ProjectModel
 * @uses \PhpAiToolkit\DocGen\Render\SiteUrl
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex
 */
#[CoversClass(SearchIndexBuilder::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ConstantDoc::class)]
#[UsesClass(DiffHtml::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(SiteUrl::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
final class SearchIndexBuilderTest extends TestCase
{
    public function testBuildEmitsEntriesForPublicSymbols(): void
    {
        $classSummary = new DocBlock('Widget summary.', '', [], null, null, [], [], [], [], [], [], null, false, '/** */');
        $methodSummary = new DocBlock('Runs the widget.', '', [], null, null, [], [], [], [], [], [], null, false, '/** */');
        $widget = new ClassLikeDoc(
            'Demo\Widget',
            'Widget',
            'Demo',
            'class',
            'demo/pkg',
            'src/Widget.php',
            5,
            30,
            false,
            true,
            [],
            [],
            [],
            [new ConstantDoc('LIMIT', 'public', '10', null, 7)],
            [],
            [new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('void', null), $methodSummary, 10, 12)],
            [],
            null,
            $classSummary,
            [],
            false,
        );
        $function = new FunctionDoc('Demo\greet', 'greet', 'Demo', 'demo/pkg', 'src/functions.php', 3, 5, [], new TypeSignature('string', null), null, [], false);
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [$widget], [$function], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);

        $expected = <<<'JS'
window.__DOCGEN_INDEX__=[{"n":"Widget","f":"Demo\\Widget","k":"class","u":"demo/pkg/Demo/class.Widget.html","s":"Widget summary."},{"n":"Widget::run","f":"Demo\\Widget::run()","k":"method","u":"demo/pkg/Demo/class.Widget.html#method.run","s":"Runs the widget."},{"n":"Widget::LIMIT","f":"Demo\\Widget::LIMIT","k":"constant","u":"demo/pkg/Demo/class.Widget.html#constant.LIMIT","s":""},{"n":"greet","f":"Demo\\greet()","k":"function","u":"demo/pkg/Demo/function.greet.html","s":""}];
JS;

        self::assertSame($expected . "\n", (new SearchIndexBuilder())->build($model));
    }

    public function testBuildExcludesDevSymbolsAndPrivateMembers(): void
    {
        $widget = new ClassLikeDoc(
            'Demo\Widget',
            'Widget',
            'Demo',
            'class',
            'demo/pkg',
            'src/Widget.php',
            5,
            30,
            false,
            true,
            [],
            [],
            [],
            [new ConstantDoc('SECRET', 'private', "'s'", null, 7)],
            [],
            [new MethodDoc('hide', 'private', false, false, false, [], new TypeSignature('void', null), null, 10, 12)],
            [],
            null,
            null,
            [],
            false,
        );
        $devClass = new ClassLikeDoc('Demo\DevTool', 'DevTool', 'Demo', 'class', 'demo/pkg', 'tests/DevTool.php', 3, 5, false, true, [], [], [], [], [], [], [], null, null, [], true);
        $devFunction = new FunctionDoc('Demo\devGreet', 'devGreet', 'Demo', 'demo/pkg', 'tests/functions.php', 3, 5, [], new TypeSignature('string', null), null, [], true);
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [$widget, $devClass], [$devFunction], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);

        $js = (new SearchIndexBuilder())->build($model);

        self::assertStringContainsString('"n":"Widget"', $js);
        self::assertStringNotContainsString('DevTool', $js);
        self::assertStringNotContainsString('devGreet', $js);
        self::assertStringNotContainsString('SECRET', $js);
        self::assertStringNotContainsString('hide', $js);
    }

    public function testMemberItemsListsPublicMethodsAndConstants(): void
    {
        $methodSummary = new DocBlock('Runs the widget.', '', [], null, null, [], [], [], [], [], [], null, false, '/** */');
        $widget = new ClassLikeDoc(
            'Demo\Widget',
            'Widget',
            'Demo',
            'class',
            'demo/pkg',
            'src/Widget.php',
            5,
            30,
            false,
            true,
            [],
            [],
            [],
            [new ConstantDoc('LIMIT', 'public', '10', null, 7), new ConstantDoc('SECRET', 'private', "'s'", null, 8)],
            [],
            [
                new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('void', null), $methodSummary, 10, 12),
                new MethodDoc('hide', 'private', false, false, false, [], new TypeSignature('void', null), null, 14, 16),
            ],
            [],
            null,
            null,
            [],
            false,
        );

        self::assertSame(
            [
                ['n' => 'Widget::run', 'f' => 'Demo\Widget::run()', 'k' => 'method', 'u' => 'p.html#method.run', 's' => 'Runs the widget.'],
                ['n' => 'Widget::LIMIT', 'f' => 'Demo\Widget::LIMIT', 'k' => 'constant', 'u' => 'p.html#constant.LIMIT', 's' => ''],
            ],
            (new SearchIndexBuilder())->memberItems($widget, 'p.html'),
        );
    }

    public function testItemBuildsCompactKeys(): void
    {
        self::assertSame(
            ['n' => 'N', 'f' => 'F', 'k' => 'class', 'u' => 'u.html', 's' => 'short'],
            (new SearchIndexBuilder())->item('N', 'F', 'class', 'u.html', 'short'),
        );
    }

    public function testItemTruncatesLongSummaries(): void
    {
        $item = (new SearchIndexBuilder())->item('N', 'F', 'class', 'u.html', str_repeat('a', 130));

        self::assertSame(str_repeat('a', 119) . '…', $item['s']);
    }

    public function testEncodeWritesOneCompactJsonObject(): void
    {
        self::assertSame(
            '{"n":"Widget","f":"Demo\\\\Widget","k":"class","u":"demo/pkg/Demo/class.Widget.html","s":"A widget."}',
            (new SearchIndexBuilder())->encode((new SearchIndexBuilder())->item('Widget', 'Demo\Widget', 'class', 'demo/pkg/Demo/class.Widget.html', 'A widget.')),
        );
    }

    public function testEncodeKeepsUnicodeAndSlashesReadable(): void
    {
        self::assertSame(
            '{"n":"N","f":"F","k":"class","u":"a/b.html","s":"日本語"}',
            (new SearchIndexBuilder())->encode((new SearchIndexBuilder())->item('N', 'F', 'class', 'a/b.html', '日本語')),
        );
    }
}
