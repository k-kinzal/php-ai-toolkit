<?php

declare(strict_types=1);

namespace Tests\Unit\Input;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Input\StructuredFile
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\Entry
 */
#[CoversClass(\Guard\Input\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Entry::class)]
final class StructuredFileTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testValueKeepsMissingInputDistinctFromMalformedContent(): void
    {
        $record = new \Guard\Input\FileRecord('/missing', 'missing', new \Guard\Input\Entry(false, false, false, '/missing'));
        self::assertNull((new \Guard\Input\StructuredFile($record, false, null, null))->value());
        $invalid = new \Guard\Input\StructuredFile($record, true, null, new JsonException('Malformed JSON'));
        $this->expectException(JsonException::class);
        $invalid->value();
    }
}
