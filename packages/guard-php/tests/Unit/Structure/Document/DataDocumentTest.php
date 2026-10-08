<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Document\DataDocument
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Structure\Document\DocumentNode
 * @uses \Guard\Structure\Document\Json5Reader
 * @uses \Guard\Structure\Document\Pointer
 * @uses \Guard\Structure\Document\Selection
 * @uses \Guard\Structure\Document\TomlEncoder
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Structure\Document\Constraint
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(\Guard\Structure\Document\DataDocument::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Structure\Document\DocumentNode::class)]
#[UsesClass(\Guard\Structure\Document\Json5Reader::class)]
#[UsesClass(\Guard\Structure\Document\Pointer::class)]
#[UsesClass(\Guard\Structure\Document\Selection::class)]
#[UsesClass(\Guard\Structure\Document\TomlEncoder::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(\Guard\Structure\Document\Constraint::class)]
#[UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
final class DataDocumentTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testWriteJsonEditsOnlySelectedValue(): void
    {
        $document = new \Guard\Structure\Document\DataDocument('json', '{"mode":"B","empty":{},"items":[],"other":null}');
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
        $document = new \Guard\Structure\Document\DataDocument('yaml', "mode: B\nother: false\n");
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
        $document = new \Guard\Structure\Document\DataDocument('neon', "parameters:\n    level: 5\n    enabled: true\n");
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
        $document = new \Guard\Structure\Document\DataDocument('toml', "[server]\nworkers = 0\nname = \"local\"\n");
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
        $document = new \Guard\Structure\Document\DataDocument('json', '{}');
        $this->expectException(JsonException::class);
        $document->decode('{');
    }
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testEncodeDataKeepsScalarTypes(): void
    {
        $document = new \Guard\Structure\Document\DataDocument('json', '{}');
        self::assertSame("{\n    \"flag\": false,\n    \"ratio\": 1.0\n}\n", $document->encodeData((object) ['flag' => false, 'ratio' => 1.0]));
    }
}
