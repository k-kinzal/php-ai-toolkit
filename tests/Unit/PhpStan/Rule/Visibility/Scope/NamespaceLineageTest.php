<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility\Scope;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage;

/**
 * @covers \Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage
 */
#[CoversClass(NamespaceLineage::class)]
final class NamespaceLineageTest extends TestCase
{
    public function testOfReturnsNamespacePart(): void
    {
        $lineage = new NamespaceLineage();

        self::assertSame('App\Domain', $lineage->of('App\Domain\Order'));
    }

    public function testParentOfReturnsDirectParent(): void
    {
        $lineage = new NamespaceLineage();

        self::assertSame('App', $lineage->parentOf('App\Domain'));
    }

    public function testRootOfReturnsOutermostSegment(): void
    {
        $lineage = new NamespaceLineage();

        self::assertSame('App', $lineage->rootOf('App\Domain'));
    }

    public function testCoversPreservesSegmentBoundaries(): void
    {
        $lineage = new NamespaceLineage();

        self::assertTrue($lineage->covers('App\Domain', 'App\Domain\Model'));
        self::assertFalse($lineage->covers('App\Domain', 'App\Domains'));
    }

    public function testCommonAncestorOfReturnsDeepestSharedNamespace(): void
    {
        $lineage = new NamespaceLineage();

        self::assertSame('App', $lineage->commonAncestorOf('App\Domain', 'App\Http'));
    }
}
