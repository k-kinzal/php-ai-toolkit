<?php

declare(strict_types=1);

namespace Tests\Unit\Input;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Input\FileRecord
 * @uses \Guard\Input\Entry
 */
#[CoversClass(\Guard\Input\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Entry::class)]
final class FileRecordTest extends TestCase
{
    public function testKeepsReportSpellingSeparateFromPhysicalIdentity(): void
    {
        $record = new \Guard\Input\FileRecord('/root/link.php', 'link.php', new \Guard\Input\Entry(false, true, true, '/root/A.php'));
        self::assertSame('link.php', $record->relativePath);
        self::assertSame('/root/A.php', $record->entry->identity);
    }
}
