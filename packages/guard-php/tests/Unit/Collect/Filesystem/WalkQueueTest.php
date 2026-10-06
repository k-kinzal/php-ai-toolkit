<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Filesystem\WalkQueue
 * @uses \Guard\Collect\Filesystem\Route
 */
#[CoversClass(\Guard\Collect\Filesystem\WalkQueue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Route::class)]
final class WalkQueueTest extends TestCase
{
    public function testAddMergesOverlappingRequestsBeforeTheChildDirectoryRuns(): void
    {
        $queue = new \Guard\Collect\Filesystem\WalkQueue();
        $first = new \Guard\Collect\Filesystem\Route('one', [], [], []);
        $second = new \Guard\Collect\Filesystem\Route('two', [], [], []);
        $queue->add('/root/deep/child', $first);
        $queue->add('/root', $second);
        self::assertSame(['/root', [$second]], $queue->next());
        $queue->add('/root/deep/child', $second);
        self::assertSame(['/root/deep/child', [$first, $second]], $queue->next());
    }
    public function testNextTerminatesAfterEveryPendingDirectory(): void
    {
        $queue = new \Guard\Collect\Filesystem\WalkQueue();
        self::assertNull($queue->next());
        $queue->add('/root', new \Guard\Collect\Filesystem\Route('one', [], [], []));
        self::assertNotNull($queue->next());
        self::assertNull($queue->next());
    }
}
