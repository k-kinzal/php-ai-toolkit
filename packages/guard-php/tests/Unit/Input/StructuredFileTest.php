<?php

declare(strict_types=1);

namespace Tests\Unit\Input;

use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\StructuredFile;
use JsonException;
use Nette\Neon\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Input\StructuredFile
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\Entry
 */
#[CoversClass(StructuredFile::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(Entry::class)]
final class StructuredFileTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testValueKeepsMissingInputDistinctFromMalformedContent(): void
    {
        $record = new FileRecord('/missing', 'missing', new Entry(false, false, false, '/missing'));
        self::assertNull((new StructuredFile($record, false, null, null))->value());
        $invalid = new StructuredFile($record, true, null, new JsonException('Malformed JSON'));
        $this->expectException(JsonException::class);
        $invalid->value();
    }
}
