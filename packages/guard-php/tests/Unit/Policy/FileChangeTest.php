<?php

declare(strict_types=1);

namespace Tests\Unit\Policy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\FileChange
 */
#[CoversClass(\Guard\Policy\FileChange::class)]
final class FileChangeTest extends TestCase
{
    public function testRetainsBothVersionsForConcurrencyChecks(): void
    {
        $change = new \Guard\Policy\FileChange('/project/a', 'before', 'after');
        self::assertSame('before', $change->original);
        self::assertSame('after', $change->replacement);
    }

}
