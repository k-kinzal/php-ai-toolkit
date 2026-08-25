<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Diff;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Render\Diff\DiffHtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Render\Diff\DiffHtml
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus
 */
#[CoversClass(DiffHtml::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
final class DiffHtmlTest extends TestCase
{
    public function testIsActiveOnlyWhileAComparisonIsRendered(): void
    {
        self::assertFalse((new DiffHtml())->isActive());
        self::assertTrue((new DiffHtml(new DiffIndex('main', 'HEAD')))->isActive());
    }

    public function testStatusOfReadsTheRecordedStateAndDefaultsToUnchanged(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark('c:demo\engine', DiffStatus::ADDED);

        self::assertSame(DiffStatus::ADDED, (new DiffHtml($index))->statusOf('c:demo\engine'));
        self::assertSame(DiffStatus::SAME, (new DiffHtml($index))->statusOf('c:demo\other'));
        self::assertSame(DiffStatus::SAME, (new DiffHtml())->statusOf('c:demo\engine'));
    }

    public function testMarkRendersNothingOutsideAComparison(): void
    {
        self::assertSame('', (new DiffHtml())->mark(DiffStatus::ADDED));
        self::assertSame(' data-diff="added"', (new DiffHtml(new DiffIndex('main', 'HEAD')))->mark(DiffStatus::ADDED));
    }

    public function testAttributeRendersTheStateRecordedUnderOneKey(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark('c:demo\engine', DiffStatus::REMOVED);

        self::assertSame(' data-diff="removed"', (new DiffHtml($index))->attribute('c:demo\engine'));
        self::assertSame(' data-diff="same"', (new DiffHtml($index))->attribute('c:demo\other'));
    }

    public function testCombineFoldsTheStatesOfSeveralElementsIntoOne(): void
    {
        $diff = new DiffHtml(new DiffIndex('main', 'HEAD'));

        self::assertSame(DiffStatus::ADDED, $diff->combine([DiffStatus::ADDED, DiffStatus::ADDED]));
        self::assertSame(DiffStatus::MODIFIED, $diff->combine([DiffStatus::SAME, DiffStatus::ADDED]));
    }

    public function testCombinedRendersTheFoldedStateAsOneAttribute(): void
    {
        self::assertSame(
            ' data-diff="modified"',
            (new DiffHtml(new DiffIndex('main', 'HEAD')))->combined([DiffStatus::SAME, DiffStatus::REMOVED]),
        );
    }

    public function testUnchangedMarksASectionNoRevisionCanChange(): void
    {
        self::assertSame(' data-diff="same"', (new DiffHtml(new DiffIndex('main', 'HEAD')))->unchanged());
        self::assertSame('', (new DiffHtml())->unchanged());
    }

    public function testClassLikeRendersTheAttributeOfOneSymbol(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->classLike('Demo\Engine'), DiffStatus::ADDED);

        self::assertSame(' data-diff="added"', (new DiffHtml($index))->classLike('Demo\Engine'));
    }

    public function testClassLikeStatusReadsTheStateOfOneSymbol(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->classLike('Demo\Engine'), DiffStatus::REMOVED);

        self::assertSame(DiffStatus::REMOVED, (new DiffHtml($index))->classLikeStatus('Demo\Engine'));
    }

    public function testHeaderRendersTheStateOfOneDeclarationHead(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->header('Demo\Engine'), DiffStatus::MODIFIED);

        self::assertSame(' data-diff="modified"', (new DiffHtml($index))->header('Demo\Engine'));
    }

    public function testMemberRendersTheAttributeOfOneMember(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->member('Demo\Engine', DiffKey::METHOD, 'run'), DiffStatus::ADDED);

        self::assertSame(' data-diff="added"', (new DiffHtml($index))->member('Demo\Engine', DiffKey::METHOD, 'run'));
    }

    public function testMemberKeyIsWhereTheStateOfAMemberIsRecorded(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $diff = new DiffHtml($index);
        $index->mark($diff->memberKey('Demo\Engine', DiffKey::PROPERTY, 'count'), DiffStatus::ADDED);

        self::assertSame(DiffStatus::ADDED, $diff->statusOf($diff->memberKey('Demo\Engine', DiffKey::PROPERTY, 'count')));
    }

    public function testMemberStatusReadsTheStateOfOneMember(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->member('Demo\Engine', DiffKey::CONSTANT, 'LIMIT'), DiffStatus::MODIFIED);

        self::assertSame(DiffStatus::MODIFIED, (new DiffHtml($index))->memberStatus('Demo\Engine', DiffKey::CONSTANT, 'LIMIT'));
    }

    public function testMethodRendersTheAttributeOfOneMethod(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->member('Demo\Engine', DiffKey::METHOD, 'run'), DiffStatus::MODIFIED);

        self::assertSame(' data-diff="modified"', (new DiffHtml($index))->method('Demo\Engine', 'run'));
    }

    public function testMethodKeyNamesTheOwnerOfTheParameterStates(): void
    {
        self::assertSame(
            'm:demo\engine::method.run',
            (new DiffHtml(new DiffIndex('main', 'HEAD')))->methodKey('Demo\Engine', 'run'),
        );
    }

    public function testPropertyRendersTheAttributeOfOneProperty(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->member('Demo\Engine', DiffKey::PROPERTY, 'count'), DiffStatus::REMOVED);

        self::assertSame(' data-diff="removed"', (new DiffHtml($index))->property('Demo\Engine', 'count'));
    }

    public function testConstantRendersTheAttributeOfOneConstant(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->member('Demo\Engine', DiffKey::CONSTANT, 'LIMIT'), DiffStatus::ADDED);

        self::assertSame(' data-diff="added"', (new DiffHtml($index))->constant('Demo\Engine', 'LIMIT'));
    }

    public function testEnumCaseRendersTheAttributeOfOneCase(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->member('Demo\Status', DiffKey::ENUM_CASE, 'Active'), DiffStatus::ADDED);

        self::assertSame(' data-diff="added"', (new DiffHtml($index))->enumCase('Demo\Status', 'Active'));
    }

    public function testFunctionSymbolRendersTheAttributeOfOneFunction(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->functionSymbol('Demo\greet'), DiffStatus::ADDED);

        self::assertSame(' data-diff="added"', (new DiffHtml($index))->functionSymbol('Demo\greet'));
    }

    public function testFunctionKeyNamesTheOwnerOfTheParameterStates(): void
    {
        self::assertSame('f:demo\greet', (new DiffHtml(new DiffIndex('main', 'HEAD')))->functionKey('Demo\greet'));
    }

    public function testParameterRendersTheAttributeOfOneParameter(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->parameter('f:demo\greet', 'name'), DiffStatus::ADDED);

        self::assertSame(' data-diff="added"', (new DiffHtml($index))->parameter('f:demo\greet', 'name'));
    }

    public function testParameterStatusReadsTheStateOfOneParameter(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->parameter('f:demo\greet', 'name'), DiffStatus::REMOVED);

        self::assertSame(DiffStatus::REMOVED, (new DiffHtml($index))->parameterStatus('f:demo\greet', 'name'));
    }

    public function testHeaderStatusReadsTheStateOfOneDeclarationHead(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->header('Demo\Engine'), DiffStatus::MODIFIED);

        self::assertSame(DiffStatus::MODIFIED, (new DiffHtml($index))->headerStatus('Demo\Engine'));
        self::assertSame(DiffStatus::SAME, (new DiffHtml())->headerStatus('Demo\Engine'));
    }

    public function testReturnTypeRendersTheAttributeOfADeclarationReturn(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->returnType('f:demo\greet'), DiffStatus::MODIFIED);

        self::assertSame(' data-diff="modified"', (new DiffHtml($index))->returnType('f:demo\greet'));
        self::assertSame('', (new DiffHtml())->returnType('f:demo\greet'));
    }

    public function testThrowsTagsRendersTheAttributeOfTheDocumentedThrows(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->throwsTags('f:demo\greet'), DiffStatus::ADDED);

        self::assertSame(' data-diff="added"', (new DiffHtml($index))->throwsTags('f:demo\greet'));
    }

    public function testSymbolStatusAnswersForClassesAndForFunctions(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->classLike('Demo\Engine'), DiffStatus::ADDED);
        $index->mark($index->keys()->functionSymbol('Demo\greet'), DiffStatus::REMOVED);
        $diff = new DiffHtml($index);

        self::assertSame(DiffStatus::ADDED, $diff->symbolStatus('class', 'Demo\Engine'));
        self::assertSame(DiffStatus::REMOVED, $diff->symbolStatus('function', 'Demo\greet'));
    }

    public function testNamespaceStatusReadsTheStateOfOneScope(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->namespaceName('demo/pkg', 'Demo\Core'), DiffStatus::ADDED);

        self::assertSame(DiffStatus::ADDED, (new DiffHtml($index))->namespaceStatus('demo/pkg', 'Demo\Core'));
    }

    public function testPackageStatusReadsTheStateOfOnePackage(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->package('demo/pkg'), DiffStatus::MODIFIED);

        self::assertSame(DiffStatus::MODIFIED, (new DiffHtml($index))->packageStatus('demo/pkg'));
    }

    public function testDocumentStatusReadsTheStateOfOneDocument(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark($index->keys()->document('demo/pkg', 'docs/guide.md'), DiffStatus::MODIFIED);

        self::assertSame(DiffStatus::MODIFIED, (new DiffHtml($index))->documentStatus('demo/pkg', 'docs/guide.md'));
    }

    public function testBaseSourceReadsAFileFromTheBaseCheckout(): void
    {
        $root = sys_get_temp_dir() . '/docgen-diffhtml-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Engine.php', '<?php');

        self::assertSame('<?php', (new DiffHtml(new DiffIndex('main', 'HEAD', $root)))->baseSource('src/Engine.php'));
        self::assertNull((new DiffHtml())->baseSource('src/Engine.php'));
    }

    public function testBaseLabelNamesTheBaseRevision(): void
    {
        self::assertSame('2f0c1a2', (new DiffHtml(new DiffIndex('2f0c1a2', 'HEAD')))->baseLabel());
        self::assertSame('', (new DiffHtml())->baseLabel());
    }

    public function testHeadLabelNamesTheHeadRevision(): void
    {
        self::assertSame('working tree', (new DiffHtml(new DiffIndex('main', 'working tree')))->headLabel());
        self::assertSame('', (new DiffHtml())->headLabel());
    }

    public function testDigestNamesTheComparisonASiteDisplaysOrTheAbsenceOfOne(): void
    {
        self::assertSame('none', (new DiffHtml())->digest());
        self::assertSame((new DiffIndex('main', 'HEAD'))->digest(), (new DiffHtml(new DiffIndex('main', 'HEAD')))->digest());
    }
}
