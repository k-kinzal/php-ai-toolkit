<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Layer;

use PhpAiToolkit\DocGen\Analysis\Layer\LayerAssigner;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerCollector;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerDefinition;
use PhpAiToolkit\DocGen\Analysis\Layer\LayerModel;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

#[CoversClass(LayerAssigner::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(LayerCollector::class)]
#[UsesClass(LayerDefinition::class)]
#[UsesClass(LayerModel::class)]
final class LayerAssignerTest extends TestCase
{
    public function testAssignReturnsLayerNamesInDefinitionOrderWithOneHitPerLayer(): void
    {
        $classLike = new ClassLikeDoc('Acme\Domain\Order', 'Order', 'Acme\Domain', 'class', 'root', 'src/Domain/Order.php', 1, 10, false, true, [], [], [], [], [], [], [], null, null, [], false);
        $model = new LayerModel([
            new LayerDefinition('Support', [new LayerCollector('directory', 'src/.*'), new LayerCollector('namespace', 'Acme')]),
            new LayerDefinition('Domain', [new LayerCollector('directory', 'src/Domain/.*')]),
            new LayerDefinition('Cli', [new LayerCollector('directory', 'bin/.*')]),
        ], []);

        self::assertSame(['Support', 'Domain'], (new LayerAssigner())->assign($model, $classLike));
    }

    public function testMatchesDirectoryCollectorAgainstTheFilePath(): void
    {
        $classLike = new ClassLikeDoc('Acme\Domain\Order', 'Order', 'Acme\Domain', 'class', 'root', 'src/Domain/Order.php', 1, 10, false, true, [], [], [], [], [], [], [], null, null, [], false);

        self::assertTrue((new LayerAssigner())->matches(new LayerCollector('directory', 'src/Domain/.*'), $classLike));
        self::assertFalse((new LayerAssigner())->matches(new LayerCollector('directory', 'src/Cli/.*'), $classLike));
    }

    public function testMatchesClassNameCollectorsAgainstTheFqcn(): void
    {
        $classLike = new ClassLikeDoc('Acme\Domain\Order', 'Order', 'Acme\Domain', 'class', 'root', 'src/Domain/Order.php', 1, 10, false, true, [], [], [], [], [], [], [], null, null, [], false);

        self::assertTrue((new LayerAssigner())->matches(new LayerCollector('className', 'Order$'), $classLike));
        self::assertTrue((new LayerAssigner())->matches(new LayerCollector('classNameRegex', '^Acme\\\\Domain'), $classLike));
        self::assertTrue((new LayerAssigner())->matches(new LayerCollector('namespace', '^Acme\\\\Domain\\\\'), $classLike));
        self::assertFalse((new LayerAssigner())->matches(new LayerCollector('className', '^Vendor'), $classLike));
    }

    #[WithoutErrorHandler]
    public function testMatchesReturnsFalseForUnknownOrInvalidCollectors(): void
    {
        $classLike = new ClassLikeDoc('Acme\Domain\Order', 'Order', 'Acme\Domain', 'class', 'root', 'src/Domain/Order.php', 1, 10, false, true, [], [], [], [], [], [], [], null, null, [], false);

        self::assertFalse((new LayerAssigner())->matches(new LayerCollector('bogus', '.*'), $classLike));
        self::assertFalse((new LayerAssigner())->matches(new LayerCollector('directory', '('), $classLike));
    }

    public function testPatternEscapesTheHashDelimiter(): void
    {
        self::assertSame('#src/\#tag#', (new LayerAssigner())->pattern('src/#tag'));
        self::assertSame('#src/.*#', (new LayerAssigner())->pattern('src/.*'));
    }
}
