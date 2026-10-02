<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Document\DataDocument
 * @uses \Toolkit\Guard\Document\Pointer
 * @uses \Toolkit\Guard\Document\Selection
 * @uses \Toolkit\Guard\Document\TomlEncoder
 * @uses \Toolkit\Guard\Policy\Constraint
 * @uses \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Document\DataDocument::class)]
#[UsesClass(\Toolkit\Guard\Document\Pointer::class)]
#[UsesClass(\Toolkit\Guard\Document\Selection::class)]
#[UsesClass(\Toolkit\Guard\Document\TomlEncoder::class)]
#[UsesClass(\Toolkit\Guard\Policy\Constraint::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class DataDocumentTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testWriteJsonEditsOnlySelectedValue(): void
    {
        $document = new \Toolkit\Guard\Document\DataDocument('json', '{"mode":"B","empty":{},"items":[],"other":null}');
        $document->write('/mode', 'A');
        self::assertSame('A', $document->read('/mode')->value);
        self::assertSame('{}', json_encode($document->read('/empty')->value));
        self::assertSame([], $document->read('/items')->value);
        self::assertNull($document->read('/other')->value);
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testReadYamlPreservesUnrelatedValues(): void
    {
        $document = new \Toolkit\Guard\Document\DataDocument('yaml', "mode: B\nother: false\n");
        $document->write('/mode', 'A');
        self::assertSame('A', $document->read('/mode')->value);
        self::assertFalse($document->read('/other')->value);
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testEncodeNeonPreservesUnrelatedValues(): void
    {
        $document = new \Toolkit\Guard\Document\DataDocument('neon', "parameters:\n    level: 5\n    enabled: true\n");
        $document->write('/parameters/level', 'max');
        self::assertSame('max', $document->read('/parameters/level')->value);
        self::assertTrue($document->read('/parameters/enabled')->value);
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testTomlEditsOnlySelectedValue(): void
    {
        $document = new \Toolkit\Guard\Document\DataDocument('toml', "[server]\nworkers = 0\nname = \"local\"\n");
        $document->write('/server/workers', 1);
        self::assertSame(1, $document->read('/server/workers')->value);
        self::assertSame('local', $document->read('/server/name')->value);
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testDecodeRejectsMalformedJson(): void
    {
        $document = new \Toolkit\Guard\Document\DataDocument('json', '{}');
        $this->expectException(JsonException::class);
        $document->decode('{');
    }
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testEncodeDataKeepsScalarTypes(): void
    {
        $document = new \Toolkit\Guard\Document\DataDocument('json', '{}');
        self::assertSame("{\n    \"flag\": false,\n    \"ratio\": 1.0\n}\n", $document->encodeData((object) ['flag' => false, 'ratio' => 1.0]));
    }
}
