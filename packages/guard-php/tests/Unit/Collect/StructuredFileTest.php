<?php

declare(strict_types=1);

namespace Tests\Unit\Collect;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\StructuredFile
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\Filesystem\Entry
 */
#[CoversClass(\Guard\Collect\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Entry::class)]
final class StructuredFileTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testValueKeepsMissingInputDistinctFromMalformedContent(): void
    {
        $record = new \Guard\Collect\FileRecord('/missing', 'missing', new \Guard\Collect\Filesystem\Entry(false, false, false, '/missing'));
        self::assertNull((new \Guard\Collect\StructuredFile($record, false, null, null))->value());
        $invalid = new \Guard\Collect\StructuredFile($record, true, null, new JsonException('Malformed JSON'));
        $this->expectException(JsonException::class);
        $invalid->value();
    }
}
