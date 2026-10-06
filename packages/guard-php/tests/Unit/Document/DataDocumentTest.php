<?php

declare(strict_types=1);

namespace Tests\Unit\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Document\DataDocument
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Document\DocumentNode
 * @uses \Guard\Document\Json5Reader
 * @uses \Guard\Document\Pointer
 * @uses \Guard\Document\Selection
 * @uses \Guard\Document\TomlEncoder
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\Constraint
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Document\DataDocument::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Document\DocumentNode::class)]
#[UsesClass(\Guard\Document\Json5Reader::class)]
#[UsesClass(\Guard\Document\Pointer::class)]
#[UsesClass(\Guard\Document\Selection::class)]
#[UsesClass(\Guard\Document\TomlEncoder::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\Constraint::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class DataDocumentTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testWriteJsonEditsOnlySelectedValue(): void
    {
        $document = new \Guard\Document\DataDocument('json', '{"mode":"B","empty":{},"items":[],"other":null}');
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
        $document = new \Guard\Document\DataDocument('yaml', "mode: B\nother: false\n");
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
        $document = new \Guard\Document\DataDocument('neon', "parameters:\n    level: 5\n    enabled: true\n");
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
        $document = new \Guard\Document\DataDocument('toml', "[server]\nworkers = 0\nname = \"local\"\n");
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
        $document = new \Guard\Document\DataDocument('json', '{}');
        $this->expectException(JsonException::class);
        $document->decode('{');
    }
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testEncodeDataKeepsScalarTypes(): void
    {
        $document = new \Guard\Document\DataDocument('json', '{}');
        self::assertSame("{\n    \"flag\": false,\n    \"ratio\": 1.0\n}\n", $document->encodeData((object) ['flag' => false, 'ratio' => 1.0]));
    }
}
