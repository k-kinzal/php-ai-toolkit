<?php

declare(strict_types=1);

namespace Tests\Unit\Structure;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\ParsedDocument
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Document\DataDocument
 * @uses \Guard\Document\DocumentNode
 * @uses \Guard\Document\Json5Reader
 * @uses \Guard\Document\PhpConfigReader
 * @uses \Guard\Document\PhpDocument
 * @uses \Guard\Document\Pointer
 * @uses \Guard\Document\Selection
 * @uses \Guard\Document\TomlEncoder
 * @uses \Guard\Document\XmlDocument
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\Constraint
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Structure\ParsedDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\DataDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\DocumentNode::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\Json5Reader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\PhpConfigReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\PhpDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\Pointer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\TomlEncoder::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Document\XmlDocument::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Constraint::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class ParsedDocumentTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testCopyIsolatesMutableDocumentsBetweenPolicies(): void
    {
        $value = new \Guard\Structure\ParsedDocument(new \Guard\Document\XmlDocument('<app value="0"/>'), '<app value="0"/>');
        $copy = $value->copy();
        $copy->write('/app/@value', '1');
        self::assertSame('0', $value->copy()->read('/app/@value')->value);
        self::assertSame('1', $copy->read('/app/@value')->value);
    }
    /**

     */
    public function testOriginalKeepsBytesThatXmlSerializationWouldNormalize(): void
    {
        $source = '<app value="0"/>';
        $value = new \Guard\Structure\ParsedDocument(new \Guard\Document\XmlDocument($source), $source);
        self::assertSame($source, $value->original());
        self::assertNotSame($source, $value->copy()->encode());
    }
}
