<?php

declare(strict_types=1);

namespace Tests\Unit\Collect;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\FileRecord
 * @uses \Guard\Collect\Filesystem\Entry
 */
#[CoversClass(\Guard\Collect\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Entry::class)]
final class FileRecordTest extends TestCase
{
    public function testKeepsReportSpellingSeparateFromPhysicalIdentity(): void
    {
        $record = new \Guard\Collect\FileRecord('/root/link.php', 'link.php', new \Guard\Collect\Filesystem\Entry(false, true, true, '/root/A.php'));
        self::assertSame('link.php', $record->relativePath);
        self::assertSame('/root/A.php', $record->entry->identity);
    }
}
