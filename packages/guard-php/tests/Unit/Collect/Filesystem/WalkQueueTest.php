<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Filesystem;

use Guard\Collect\Filesystem\Route;
use Guard\Collect\Filesystem\WalkQueue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Filesystem\WalkQueue
 * @uses \Guard\Collect\Filesystem\Route
 */
#[CoversClass(WalkQueue::class)]
#[UsesClass(Route::class)]
final class WalkQueueTest extends TestCase
{
    public function testAddMergesOverlappingRequestsBeforeTheChildDirectoryRuns(): void
    {
        $queue = new WalkQueue();
        $first = new Route('one', [], [], []);
        $second = new Route('two', [], [], []);
        $queue->add('/root/deep/child', $first);
        $queue->add('/root', $second);
        self::assertSame(['/root', [$second]], $queue->next());
        $queue->add('/root/deep/child', $second);
        self::assertSame(['/root/deep/child', [$first, $second]], $queue->next());
    }
    public function testNextTerminatesAfterEveryPendingDirectory(): void
    {
        $queue = new WalkQueue();
        self::assertNull($queue->next());
        $queue->add('/root', new Route('one', [], [], []));
        self::assertNotNull($queue->next());
        self::assertNull($queue->next());
    }
}
