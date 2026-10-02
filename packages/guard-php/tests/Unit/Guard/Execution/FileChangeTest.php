<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Execution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Execution\FileChange
 */
#[CoversClass(\Toolkit\Guard\Execution\FileChange::class)]
final class FileChangeTest extends TestCase
{
    public function testRetainsBothVersionsForConcurrencyChecks(): void
    {
        $change = new \Toolkit\Guard\Execution\FileChange('/project/a', 'before', 'after');
        self::assertSame('before', $change->original);
        self::assertSame('after', $change->replacement);
    }

}
