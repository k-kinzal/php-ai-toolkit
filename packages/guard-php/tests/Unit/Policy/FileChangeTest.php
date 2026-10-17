<?php

declare(strict_types=1);

namespace Tests\Unit\Policy;

use Guard\Policy\FileChange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\FileChange
 */
#[CoversClass(FileChange::class)]
final class FileChangeTest extends TestCase
{
    public function testRetainsBothVersionsForConcurrencyChecks(): void
    {
        $change = new FileChange('/project/a', 'before', 'after');
        self::assertSame('before', $change->original);
        self::assertSame('after', $change->replacement);
    }

}
