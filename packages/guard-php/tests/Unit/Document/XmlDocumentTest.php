<?php

declare(strict_types=1);

namespace Tests\Unit\Document;

use DOMElement;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Document\XmlDocument
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Document\Selection
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Document\XmlDocument::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Document\Selection::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class XmlDocumentTest extends TestCase
{
    public function testDomExposesTheAlreadyParsedTreeAndIsolatesClones(): void
    {
        $document = new \Guard\Document\XmlDocument('<count>1</count>');
        $copy = clone $document;
        self::assertSame($copy->node('/'), $copy->dom());
        $element = $copy->dom()->documentElement;
        self::assertInstanceOf(DOMElement::class, $element);
        $element->textContent = '2';
        self::assertStringContainsString('<count>1</count>', $document->encode());
        self::assertStringContainsString('<count>2</count>', $copy->encode());
    }
    /**
     * @throws JsonException
     */
    public function testWriteCreatesAttributesAndEscapesText(): void
    {
        $document = new \Guard\Document\XmlDocument('<settings><name>before</name></settings>');
        $document->write('/settings/@strict', 'true');
        $document->write('/settings/name', 'A & B');
        self::assertSame('true', $document->read('/settings/@strict')->value);
        self::assertStringContainsString('A &amp; B', $document->encode());
    }

    public function testRejectsExternalEntities(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        new \Guard\Document\XmlDocument('<!DOCTYPE x [<!ENTITY a SYSTEM "file:///etc/passwd">]><x>&a;</x>');
    }

    /**
     * @throws JsonException
     */
    public function testReadRejectsAmbiguousSelectors(): void
    {
        $document = new \Guard\Document\XmlDocument('<x><v>A</v><v>B</v></x>');
        $this->expectException(\Guard\Policy\PolicyException::class);
        $document->read('/x/v');
    }

    /**
     */
    public function testRefusesToDestroyChildren(): void
    {
        $document = new \Guard\Document\XmlDocument('<x><v>A</v></x>');
        $this->expectException(\Guard\Policy\PolicyException::class);
        $document->write('/x', 'B');
    }

    public function testNodeReportsMissingValues(): void
    {
        $document = new \Guard\Document\XmlDocument('<x/>');
        self::assertNull($document->node('/x/@missing'));
    }
    /**
     */
    public function testEncodePreservesComments(): void
    {
        $document = new \Guard\Document\XmlDocument('<x><!--keep--><v>A</v></x>');
        $document->write('/x/v', 'B');
        self::assertStringContainsString('<!--keep-->', $document->encode());
    }
    public function testNodeRejectsInvalidXPathWithoutEmittingWarnings(): void
    {
        $document = new \Guard\Document\XmlDocument('<x/>');
        $this->expectException(\Guard\Policy\PolicyException::class);
        $document->node('/x[');
    }

    public function testEmptyInputIsRejectedBeforeParsing(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        new \Guard\Document\XmlDocument('');
    }
}
