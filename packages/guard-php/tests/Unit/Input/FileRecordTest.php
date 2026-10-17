<?php

declare(strict_types=1);

namespace Tests\Unit\Input;

use Guard\Input\Entry;
use Guard\Input\FileRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Input\FileRecord
 * @uses \Guard\Input\Entry
 */
#[CoversClass(FileRecord::class)]
#[UsesClass(Entry::class)]
final class FileRecordTest extends TestCase
{
    public function testKeepsReportSpellingSeparateFromPhysicalIdentity(): void
    {
        $record = new FileRecord('/root/link.php', 'link.php', new Entry(false, true, true, '/root/A.php'));
        self::assertSame('link.php', $record->relativePath);
        self::assertSame('/root/A.php', $record->entry->identity);
    }
}
