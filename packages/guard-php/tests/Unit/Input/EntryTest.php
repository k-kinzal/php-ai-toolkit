<?php

declare(strict_types=1);

namespace Tests\Unit\Input;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Input\Entry
 */
#[CoversClass(\Guard\Input\Entry::class)]
final class EntryTest extends TestCase
{
    public function testSymlinkMetadataCanRetainItsTargetType(): void
    {
        $entry = new \Guard\Input\Entry(true, false, true, '/physical');
        self::assertTrue($entry->directory);
        self::assertTrue($entry->link);
        self::assertFalse($entry->file);
    }
}
