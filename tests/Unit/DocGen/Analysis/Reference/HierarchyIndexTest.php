<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Reference;

use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HierarchyIndex::class)]
#[UsesClass(ClassLikeDoc::class)]
final class HierarchyIndexTest extends TestCase
{
    public function testBuildSeparatesInterfaceExtendsFromClassExtends(): void
    {
        $index = new HierarchyIndex();
        $index->build([
            new ClassLikeDoc('Demo\Child', 'Child', 'Demo', 'class', 'demo/app', 'src/Child.php', 1, 5, false, false, ['Demo\Base'], [], [], [], [], [], [], null, null, [], false),
            new ClassLikeDoc('Demo\Wide', 'Wide', 'Demo', 'interface', 'demo/app', 'src/Wide.php', 1, 5, false, false, ['Demo\Narrow'], [], [], [], [], [], [], null, null, [], false),
        ]);

        self::assertSame(['Demo\Child'], $index->subclassesOf('Demo\Base'));
        self::assertSame(['Demo\Wide'], $index->interfaceExtendersOf('Demo\Narrow'));
        self::assertSame([], $index->subclassesOf('Demo\Narrow'));
        self::assertSame([], $index->interfaceExtendersOf('Demo\Base'));
    }

    public function testSubclassesOfReturnsSortedSubclassesForCaseInsensitiveKey(): void
    {
        $index = new HierarchyIndex();
        $index->build([
            new ClassLikeDoc('Demo\Second', 'Second', 'Demo', 'class', 'demo/app', 'src/Second.php', 1, 5, false, false, ['Demo\Base'], [], [], [], [], [], [], null, null, [], false),
            new ClassLikeDoc('Demo\First', 'First', 'Demo', 'class', 'demo/app', 'src/First.php', 1, 5, false, false, ['DEMO\BASE'], [], [], [], [], [], [], null, null, [], false),
        ]);

        self::assertSame(['Demo\First', 'Demo\Second'], $index->subclassesOf('demo\base'));
        self::assertSame([], $index->subclassesOf('Demo\Unknown'));
    }

    public function testImplementorsOfReturnsSortedImplementors(): void
    {
        $index = new HierarchyIndex();
        $index->build([
            new ClassLikeDoc('Demo\Zulu', 'Zulu', 'Demo', 'class', 'demo/app', 'src/Zulu.php', 1, 5, false, false, [], ['Demo\Contract'], [], [], [], [], [], null, null, [], false),
            new ClassLikeDoc('Demo\Alpha', 'Alpha', 'Demo', 'class', 'demo/app', 'src/Alpha.php', 1, 5, false, false, [], ['Demo\Contract'], [], [], [], [], [], null, null, [], false),
        ]);

        self::assertSame(['Demo\Alpha', 'Demo\Zulu'], $index->implementorsOf('DEMO\CONTRACT'));
        self::assertSame([], $index->implementorsOf('Demo\Other'));
    }

    public function testInterfaceExtendersOfReturnsSortedExtendingInterfaces(): void
    {
        $index = new HierarchyIndex();
        $index->build([
            new ClassLikeDoc('Demo\Writable', 'Writable', 'Demo', 'interface', 'demo/app', 'src/Writable.php', 1, 5, false, false, ['Demo\Stream'], [], [], [], [], [], [], null, null, [], false),
            new ClassLikeDoc('Demo\Readable', 'Readable', 'Demo', 'interface', 'demo/app', 'src/Readable.php', 1, 5, false, false, ['Demo\Stream'], [], [], [], [], [], [], null, null, [], false),
        ]);

        self::assertSame(['Demo\Readable', 'Demo\Writable'], $index->interfaceExtendersOf('demo\stream'));
        self::assertSame([], $index->interfaceExtendersOf('Demo\Other'));
    }

    public function testTraitUsersOfReturnsSortedTraitUsers(): void
    {
        $index = new HierarchyIndex();
        $index->build([
            new ClassLikeDoc('Demo\Consumer', 'Consumer', 'Demo', 'class', 'demo/app', 'src/Consumer.php', 1, 5, false, false, [], [], ['Demo\Shared'], [], [], [], [], null, null, [], false),
            new ClassLikeDoc('Demo\Adopter', 'Adopter', 'Demo', 'class', 'demo/app', 'src/Adopter.php', 1, 5, false, false, [], [], ['Demo\Shared'], [], [], [], [], null, null, [], false),
        ]);

        self::assertSame(['Demo\Adopter', 'Demo\Consumer'], $index->traitUsersOf('DEMO\SHARED'));
        self::assertSame([], $index->traitUsersOf('Demo\Other'));
    }
}
