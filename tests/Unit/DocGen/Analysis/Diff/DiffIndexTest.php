<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Diff\DiffIndex;
use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\Diff\DiffStatus;

/**
 * @covers \Toolkit\DocGen\Analysis\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffKey
 * @uses \Toolkit\DocGen\Analysis\Diff\DiffStatus
 */
#[CoversClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
final class DiffIndexTest extends TestCase
{
    public function testMarkRecordsTheStateOfOneElement(): void
    {
        $index = new DiffIndex('main', 'working tree');
        $index->mark('c:demo\engine', DiffStatus::ADDED);

        self::assertSame(DiffStatus::ADDED, $index->status('c:demo\engine'));
    }

    public function testStatusTreatsAnUnrecordedElementAsUnchanged(): void
    {
        self::assertSame(DiffStatus::SAME, (new DiffIndex('main', 'HEAD'))->status('c:demo\untouched'));
    }

    public function testMarkOverwritesAnEarlierStateOfTheSameElement(): void
    {
        $index = new DiffIndex('main', 'HEAD');
        $index->mark('c:demo\engine', DiffStatus::ADDED);
        $index->mark('c:demo\engine', DiffStatus::MODIFIED);

        self::assertSame(DiffStatus::MODIFIED, $index->status('c:demo\engine'));
    }

    public function testKeysReturnsTheKeyBuilderItWasGiven(): void
    {
        $keys = new DiffKey();

        self::assertSame($keys, (new DiffIndex('main', 'HEAD', null, $keys))->keys());
        self::assertSame('c:demo\engine', (new DiffIndex('main', 'HEAD'))->keys()->classLike('Demo\Engine'));
    }

    public function testBaseLabelNamesTheComparedBaseRevision(): void
    {
        self::assertSame('2f0c1a2', (new DiffIndex('2f0c1a2', 'working tree'))->baseLabel());
    }

    public function testHeadLabelNamesTheComparedHeadRevision(): void
    {
        self::assertSame('working tree', (new DiffIndex('2f0c1a2', 'working tree'))->headLabel());
    }

    public function testBaseRootIsTheCheckoutTheBaseRevisionWasReadFrom(): void
    {
        self::assertNull((new DiffIndex('main', 'HEAD'))->baseRoot());
        self::assertSame('/tmp/checkout', (new DiffIndex('main', 'HEAD', '/tmp/checkout'))->baseRoot());
    }

    public function testBaseSourceReadsAFileFromTheBaseCheckout(): void
    {
        $root = sys_get_temp_dir() . '/docgen-index-' . bin2hex(random_bytes(4));
        mkdir($root . '/src', 0777, true);
        file_put_contents($root . '/src/Engine.php', '<?php class Engine {}');

        self::assertSame('<?php class Engine {}', (new DiffIndex('main', 'HEAD', $root))->baseSource('src/Engine.php'));
        self::assertNull((new DiffIndex('main', 'HEAD', $root))->baseSource('src/Missing.php'));
        self::assertNull((new DiffIndex('main', 'HEAD'))->baseSource('src/Engine.php'));
    }

    public function testDigestNamesTheComparisonAndNotTheCheckoutItWasReadFrom(): void
    {
        $first = new DiffIndex('main', 'HEAD', '/tmp/checkout-one');
        $second = new DiffIndex('main', 'HEAD', '/tmp/checkout-two');
        $first->mark('class:demo\\widget', DiffStatus::MODIFIED);
        $second->mark('class:demo\\widget', DiffStatus::MODIFIED);

        self::assertSame($first->digest(), $second->digest());

        $second->mark('class:demo\\engine', DiffStatus::ADDED);

        self::assertNotSame($first->digest(), $second->digest());
        self::assertNotSame($first->digest(), (new DiffIndex('other', 'HEAD'))->digest());
    }
}
