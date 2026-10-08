<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use DOMElement;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Document\XmlDocument
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Structure\Document\Selection
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(\Guard\Structure\Document\XmlDocument::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Structure\Document\Selection::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
final class XmlDocumentTest extends TestCase
{
    public function testDomExposesTheAlreadyParsedTreeAndIsolatesClones(): void
    {
        $document = new \Guard\Structure\Document\XmlDocument('<count>1</count>');
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
        $document = new \Guard\Structure\Document\XmlDocument('<settings><name>before</name></settings>');
        $document->write('/settings/@strict', 'true');
        $document->write('/settings/name', 'A & B');
        self::assertSame('true', $document->read('/settings/@strict')->value);
        self::assertStringContainsString('A &amp; B', $document->encode());
    }

    public function testRejectsExternalEntities(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        new \Guard\Structure\Document\XmlDocument('<!DOCTYPE x [<!ENTITY a SYSTEM "file:///etc/passwd">]><x>&a;</x>');
    }

    /**
     * @throws JsonException
     */
    public function testReadRejectsAmbiguousSelectors(): void
    {
        $document = new \Guard\Structure\Document\XmlDocument('<x><v>A</v><v>B</v></x>');
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $document->read('/x/v');
    }

    /**
     */
    public function testRefusesToDestroyChildren(): void
    {
        $document = new \Guard\Structure\Document\XmlDocument('<x><v>A</v></x>');
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $document->write('/x', 'B');
    }

    public function testNodeReportsMissingValues(): void
    {
        $document = new \Guard\Structure\Document\XmlDocument('<x/>');
        self::assertNull($document->node('/x/@missing'));
    }
    /**
     */
    public function testEncodePreservesComments(): void
    {
        $document = new \Guard\Structure\Document\XmlDocument('<x><!--keep--><v>A</v></x>');
        $document->write('/x/v', 'B');
        self::assertStringContainsString('<!--keep-->', $document->encode());
    }
    public function testNodeRejectsInvalidXPathWithoutEmittingWarnings(): void
    {
        $document = new \Guard\Structure\Document\XmlDocument('<x/>');
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        $document->node('/x[');
    }

    public function testEmptyInputIsRejectedBeforeParsing(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        new \Guard\Structure\Document\XmlDocument('');
    }
}
