<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Visibility\VisibilityDeclarationIndex;

/**
 * @covers \Toolkit\PhpStan\Rule\Visibility\VisibilityDeclarationIndex
 */
#[CoversClass(VisibilityDeclarationIndex::class)]
final class VisibilityDeclarationIndexTest extends TestCase
{
    public function testMemberDeclarationFollowsCollectedParents(): void
    {
        $index = new VisibilityDeclarationIndex();
        $index->add('/Base.php', [
            'class' => ['className' => 'App\Base', 'memberName' => null, 'symbol' => 'App\Base', 'kind' => 'class', 'namespace' => 'App', 'docComment' => null, 'line' => 3],
            'parents' => [],
            'members' => [['className' => 'App\Base', 'memberName' => 'run', 'symbol' => 'App\Base::run()', 'kind' => 'method', 'namespace' => 'App', 'docComment' => '/** @visibility namespace */', 'line' => 5]],
        ]);
        $index->add('/Child.php', [
            'class' => ['className' => 'App\Child', 'memberName' => null, 'symbol' => 'App\Child', 'kind' => 'class', 'namespace' => 'App', 'docComment' => null, 'line' => 3],
            'parents' => ['App\Base'],
            'members' => [],
        ]);

        self::assertSame('App\Base::run()', $index->memberDeclaration('App\Child', 'run')['symbol'] ?? null);
    }

    public function testAddRecordsClassAndDeclarations(): void
    {
        $index = new VisibilityDeclarationIndex();
        $index->add('/Order.php', [
            'class' => ['className' => 'App\Order', 'memberName' => null, 'symbol' => 'App\Order', 'kind' => 'class', 'namespace' => 'App', 'docComment' => null, 'line' => 3],
            'parents' => [],
            'members' => [],
        ]);

        self::assertCount(1, $index->declarations());
    }

    public function testClassDeclarationIsCaseInsensitive(): void
    {
        $index = new VisibilityDeclarationIndex();
        $index->add('/Order.php', [
            'class' => ['className' => 'App\Order', 'memberName' => null, 'symbol' => 'App\Order', 'kind' => 'class', 'namespace' => 'App', 'docComment' => null, 'line' => 3],
            'parents' => [],
            'members' => [],
        ]);

        self::assertSame('App\Order', $index->classDeclaration('app\order')['symbol'] ?? null);
    }

    public function testDeclarationsReturnCollectionOrder(): void
    {
        self::assertSame([], (new VisibilityDeclarationIndex())->declarations());
    }

    public function testMemberKeyNormalizesOnlyClassName(): void
    {
        self::assertSame('app\order::Run', (new VisibilityDeclarationIndex())->memberKey('App\Order', 'Run'));
    }
}
