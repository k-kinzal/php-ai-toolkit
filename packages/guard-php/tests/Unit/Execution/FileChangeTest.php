<?php

declare(strict_types=1);

namespace Tests\Unit\Execution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Execution\FileChange
 */
#[CoversClass(\Guard\Execution\FileChange::class)]
final class FileChangeTest extends TestCase
{
    public function testRetainsBothVersionsForConcurrencyChecks(): void
    {
        $change = new \Guard\Execution\FileChange('/project/a', 'before', 'after');
        self::assertSame('before', $change->original);
        self::assertSame('after', $change->replacement);
    }

}
