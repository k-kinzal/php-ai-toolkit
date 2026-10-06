<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Filesystem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Filesystem\Entry
 */
#[CoversClass(\Guard\Collect\Filesystem\Entry::class)]
final class EntryTest extends TestCase
{
    public function testSymlinkMetadataCanRetainItsTargetType(): void
    {
        $entry = new \Guard\Collect\Filesystem\Entry(true, false, true, '/physical');
        self::assertTrue($entry->directory);
        self::assertTrue($entry->link);
        self::assertFalse($entry->file);
    }
}
