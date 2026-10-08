<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Document\Json5Reader
 */
#[CoversClass(\Guard\Structure\Document\Json5Reader::class)]
final class Json5ReaderTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testDecodeReadsCommentsAndTrailingCommas(): void
    {
        $decoded = (new \Guard\Structure\Document\Json5Reader())->decode("// note\n{\"minMsi\": 80, \"mutators\": {\"@default\": true,},}\n");
        self::assertSame('{"minMsi":80,"mutators":{"@default":true}}', json_encode($decoded));
    }

    public function testStripKeepsACommentInsideAString(): void
    {
        self::assertSame("{\"note\":\"http://example.test\"}\n", (new \Guard\Structure\Document\Json5Reader())->strip("{\"note\":\"http://example.test\"}\n"));
    }

    public function testLineStopsAtTheNewline(): void
    {
        self::assertSame(7, (new \Guard\Structure\Document\Json5Reader())->line("// note\n{", 0));
    }

    public function testBlockStopsAtTheCloser(): void
    {
        self::assertSame(6, (new \Guard\Structure\Document\Json5Reader())->block('/* a */', 0));
    }

    public function testCloserSkipsSpaceBeforeTheDelimiter(): void
    {
        $reader = new \Guard\Structure\Document\Json5Reader();
        self::assertTrue($reader->closer(" \n}", 0));
        self::assertFalse($reader->closer(' 1', 0));
    }
}
