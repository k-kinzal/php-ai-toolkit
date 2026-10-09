<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Infrastructure\Git;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Infrastructure\Git\RevisionRange;

/**
 * @covers \Toolkit\DocGen\Infrastructure\Git\RevisionRange
 */
#[CoversClass(RevisionRange::class)]
final class RevisionRangeTest extends TestCase
{
    public function testStoresBothComparedRevisions(): void
    {
        $range = new RevisionRange('main', 'feature');

        self::assertSame('main', $range->base);
        self::assertSame('feature', $range->head);
    }

    public function testTreatsAMissingHeadAsTheWorkingTree(): void
    {
        $range = new RevisionRange('v1.0.0');

        self::assertSame('v1.0.0', $range->base);
        self::assertNull($range->head);
    }
}
