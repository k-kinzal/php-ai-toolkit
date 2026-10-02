<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Document\XmlDocument
 * @uses \Toolkit\Guard\Document\Selection
 * @uses \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Document\XmlDocument::class)]
#[UsesClass(\Toolkit\Guard\Document\Selection::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class XmlDocumentTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testWriteCreatesAttributesAndEscapesText(): void
    {
        $document = new \Toolkit\Guard\Document\XmlDocument('<settings><name>before</name></settings>');
        $document->write('/settings/@strict', 'true');
        $document->write('/settings/name', 'A & B');
        self::assertSame('true', $document->read('/settings/@strict')->value);
        self::assertStringContainsString('A &amp; B', $document->encode());
    }

    public function testRejectsExternalEntities(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        new \Toolkit\Guard\Document\XmlDocument('<!DOCTYPE x [<!ENTITY a SYSTEM "file:///etc/passwd">]><x>&a;</x>');
    }

    /**
     * @throws JsonException
     */
    public function testReadRejectsAmbiguousSelectors(): void
    {
        $document = new \Toolkit\Guard\Document\XmlDocument('<x><v>A</v><v>B</v></x>');
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $document->read('/x/v');
    }

    /**
     */
    public function testRefusesToDestroyChildren(): void
    {
        $document = new \Toolkit\Guard\Document\XmlDocument('<x><v>A</v></x>');
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $document->write('/x', 'B');
    }

    public function testNodeReportsMissingValues(): void
    {
        $document = new \Toolkit\Guard\Document\XmlDocument('<x/>');
        self::assertNull($document->node('/x/@missing'));
    }
    /**
     */
    public function testEncodePreservesComments(): void
    {
        $document = new \Toolkit\Guard\Document\XmlDocument('<x><!--keep--><v>A</v></x>');
        $document->write('/x/v', 'B');
        self::assertStringContainsString('<!--keep-->', $document->encode());
    }
    public function testNodeRejectsInvalidXPathWithoutEmittingWarnings(): void
    {
        $document = new \Toolkit\Guard\Document\XmlDocument('<x/>');
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $document->node('/x[');
    }

    public function testEmptyInputIsRejectedBeforeParsing(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        new \Toolkit\Guard\Document\XmlDocument('');
    }
}
