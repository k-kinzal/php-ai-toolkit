<?php

declare(strict_types=1);

namespace Tests\Unit\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Document\XmlDocument
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Document\Selection
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Document\XmlDocument::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Document\Selection::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class XmlDocumentTest extends TestCase
{
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
