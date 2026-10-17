<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use Guard\Config\Configuration;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\DirectoryListing;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Context;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use Guard\Structure\Document\Constraint;
use Guard\Structure\Document\DataDocument;
use Guard\Structure\Document\DocumentNode;
use Guard\Structure\Document\Json5Reader;
use Guard\Structure\Document\Pointer;
use Guard\Structure\Document\TomlEncoder;
use JsonException;
use Nette\Neon\Exception;
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
#[CoversClass(DataDocument::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(DocumentNode::class)]
#[UsesClass(Json5Reader::class)]
#[UsesClass(Pointer::class)]
#[UsesClass(\Guard\Structure\Document\Selection::class)]
#[UsesClass(TomlEncoder::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(Constraint::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
final class DataDocumentTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testWriteJsonEditsOnlySelectedValue(): void
    {
        $document = new DataDocument('json', '{"mode":"B","empty":{},"items":[],"other":null}');
        $document->write('/mode', 'A');
        self::assertSame('A', $document->read('/mode')->value);
        self::assertSame('{}', json_encode($document->read('/empty')->value));
        self::assertSame([], $document->read('/items')->value);
        self::assertNull($document->read('/other')->value);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testReadYamlPreservesUnrelatedValues(): void
    {
        $document = new DataDocument('yaml', "mode: B\nother: false\n");
        $document->write('/mode', 'A');
        self::assertSame('A', $document->read('/mode')->value);
        self::assertFalse($document->read('/other')->value);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testEncodeNeonPreservesUnrelatedValues(): void
    {
        $document = new DataDocument('neon', "parameters:\n    level: 5\n    enabled: true\n");
        $document->write('/parameters/level', 'max');
        self::assertSame('max', $document->read('/parameters/level')->value);
        self::assertTrue($document->read('/parameters/enabled')->value);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testTomlEditsOnlySelectedValue(): void
    {
        $document = new DataDocument('toml', "[server]\nworkers = 0\nname = \"local\"\n");
        $document->write('/server/workers', 1);
        self::assertSame(1, $document->read('/server/workers')->value);
        self::assertSame('local', $document->read('/server/name')->value);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testDecodeRejectsMalformedJson(): void
    {
        $document = new DataDocument('json', '{}');
        $this->expectException(JsonException::class);
        $document->decode('{');
    }
    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testEncodeDataKeepsScalarTypes(): void
    {
        $document = new DataDocument('json', '{}');
        self::assertSame("{\n    \"flag\": false,\n    \"ratio\": 1.0\n}\n", $document->encodeData((object) ['flag' => false, 'ratio' => 1.0]));
    }
}
