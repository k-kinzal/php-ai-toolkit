<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\ConstantDoc;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;
use Toolkit\DocGen\Analysis\Model\MethodDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Package\PackageGraph;
use Toolkit\DocGen\Render\Diff\DiffHtml;
use Toolkit\DocGen\Render\SearchIndexBuilder;
use Toolkit\DocGen\Render\SiteUrl;

/**
 * @covers \Toolkit\DocGen\Render\SearchIndexBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Analysis\Model\ConstantDoc
 * @uses \Toolkit\DocGen\Render\Diff\DiffHtml
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Analysis\Model\DocBlock
 * @uses \Toolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Render\SiteUrl
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
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
#[UsesClass(\Toolkit\Mutation\MutationContract::class)]
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

    public function testBuildPublicApiModeExcludesUnmarkedSymbolsAndRestrictedMembers(): void
    {
        $publicDoc = new DocBlock('Client API.', '', [], null, null, [], [], [], [], [], [], null, false, '', ['PUBLIC']);
        $restrictedDoc = new DocBlock('Internal operation.', '', [], null, null, [], [], [], [], [], [], null, false, '', ['namespace']);
        $client = new ClassLikeDoc(
            'Demo\Client',
            'Client',
            'Demo',
            'class',
            'demo/pkg',
            'src/Client.php',
            5,
            30,
            false,
            true,
            [],
            [],
            [],
            [new ConstantDoc('VERSION', 'public', "'1'", null, 7), new ConstantDoc('INTERNAL', 'public', "'x'", $restrictedDoc, 8)],
            [],
            [new MethodDoc('run', 'public', false, false, false, [], new TypeSignature('void', null), null, 10, 12), new MethodDoc('inspect', 'public', false, false, false, [], new TypeSignature('void', null), $restrictedDoc, 14, 16)],
            [],
            null,
            $publicDoc,
            [],
            false,
        );
        $helper = new ClassLikeDoc('Demo\Helper', 'Helper', 'Demo', 'class', 'demo/pkg', 'src/Helper.php', 3, 5, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $model = new ProjectModel('Demo Docs', '/tmp/docgen-root', [], new PackageGraph([]), [$client, $helper], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [], null, null, true);

        $js = (new SearchIndexBuilder())->build($model);

        self::assertStringContainsString('Client', $js);
        self::assertStringContainsString('run', $js);
        self::assertStringContainsString('VERSION', $js);
        self::assertStringNotContainsString('Helper', $js);
        self::assertStringNotContainsString('inspect', $js);
        self::assertStringNotContainsString('INTERNAL', $js);
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
