<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use DOMElement;
use DOMException;
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
use Guard\Structure\Document\XmlDocument;
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
#[CoversClass(XmlDocument::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(\Guard\Structure\Document\Selection::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
final class XmlDocumentTest extends TestCase
{
    public function testDomExposesTheAlreadyParsedTreeAndIsolatesClones(): void
    {
        $document = new XmlDocument('<count>1</count>');
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
        $document = new XmlDocument('<settings><name>before</name></settings>');
        $document->write('/settings/@strict', 'true');
        $document->write('/settings/name', 'A & B');
        self::assertSame('true', $document->read('/settings/@strict')->value);
        self::assertStringContainsString('A &amp; B', $document->encode());
    }

    public function testRejectsExternalEntities(): void
    {
        $this->expectException(PolicyException::class);
        new XmlDocument('<!DOCTYPE x [<!ENTITY a SYSTEM "file:///etc/passwd">]><x>&a;</x>');
    }

    /**
     * @throws JsonException
     */
    public function testReadRejectsAmbiguousSelectors(): void
    {
        $document = new XmlDocument('<x><v>A</v><v>B</v></x>');
        $this->expectException(PolicyException::class);
        $document->read('/x/v');
    }

    /**
     */
    public function testRefusesToDestroyChildren(): void
    {
        $document = new XmlDocument('<x><v>A</v></x>');
        $this->expectException(PolicyException::class);
        $document->write('/x', 'B');
    }

    public function testNodeReportsMissingValues(): void
    {
        $document = new XmlDocument('<x/>');
        self::assertNull($document->node('/x/@missing'));
    }
    /**
     */
    public function testEncodePreservesComments(): void
    {
        $document = new XmlDocument('<x><!--keep--><v>A</v></x>');
        $document->write('/x/v', 'B');
        self::assertStringContainsString('<!--keep-->', $document->encode());
    }
    public function testNodeRejectsInvalidXPathWithoutEmittingWarnings(): void
    {
        $document = new XmlDocument('<x/>');
        $this->expectException(PolicyException::class);
        $document->node('/x[');
    }

    public function testEmptyInputIsRejectedBeforeParsing(): void
    {
        $this->expectException(PolicyException::class);
        new XmlDocument('');
    }

    /**
     * @throws JsonException
     */
    public function testWriteCreatesASuiteWithoutChangingExistingSuitesOrComments(): void
    {
        $document = new XmlDocument('<phpunit><!--keep--><testsuites><testsuite name="unit"><directory>tests</directory></testsuite></testsuites></phpunit>');
        $selector = '/phpunit/testsuites/testsuite[@name="doctest"]/file';
        $document->write($selector, 'vendor/example/suite.php');
        $document->write($selector, 'vendor/example/suite.php');

        self::assertSame('vendor/example/suite.php', $document->read($selector)->value);
        self::assertSame('tests', $document->read('/phpunit/testsuites/testsuite[@name="unit"]/directory')->value);
        self::assertStringContainsString('<!--keep-->', $document->encode());
        self::assertSame(2, $document->dom()->getElementsByTagName('testsuite')->length);
    }

    /**
     * @throws JsonException
     */
    public function testWriteCreatesExtensionParentsAndParameters(): void
    {
        $document = new XmlDocument('<phpunit/>');
        $selector = '/phpunit/extensions/bootstrap[@class="Example\\Extension"]/parameter[@name="directories"]/@value';
        $document->write($selector, 'src');

        self::assertSame('src', $document->read($selector)->value);
        self::assertSame('Example\\Extension', $document->read('/phpunit/extensions/bootstrap/@class')->value);
    }

    /**
     * @throws JsonException
     */
    public function testWritePreservesQuotedPredicateValuesContainingSlashesAndXmlCharacters(): void
    {
        $document = new XmlDocument('<settings/>');
        $selector = "/settings/path[@name='a/b & c']/@value";
        $document->write($selector, '<value>');

        self::assertSame('<value>', $document->read($selector)->value);
        self::assertStringContainsString('name="a/b &amp; c"', $document->encode());
        self::assertStringContainsString('value="&lt;value&gt;"', $document->encode());
    }

    /**
     * @throws JsonException
     */
    public function testWriteReusesParentsAndAcceptsAnEmptyPredicateValue(): void
    {
        $document = new XmlDocument('<settings><path name="other" value="keep"/></settings>');
        $document->write('/settings/path[@name=""]/@value', 'new');

        self::assertSame('new', $document->read('/settings/path[@name=""]/@value')->value);
        self::assertSame('keep', $document->read('/settings/path[@name="other"]/@value')->value);
        self::assertSame(2, $document->dom()->getElementsByTagName('path')->length);
    }

    public function testWriteRejectsAnAmbiguousParent(): void
    {
        $document = new XmlDocument('<phpunit><extensions/><extensions/></phpunit>');
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('/phpunit/extensions');
        $document->write('/phpunit/extensions/bootstrap[@class="Example"]/@class', 'Example');
    }

    public function testCreateRejectsCreatingASecondRoot(): void
    {
        $document = new XmlDocument('<settings/>');
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Use the existing root');
        $document->create('/phpunit/extensions/bootstrap/@class');
    }

    public function testCreationStepsRejectsAPositionalSelector(): void
    {
        $document = new XmlDocument('<settings/>');
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Use an absolute path of named elements');
        $document->creationSteps('/settings/path[2]/@value');
    }

    /**
     * @throws JsonException
     */
    public function testWriteStillRepairsExistingNodesSelectedByComplexXPath(): void
    {
        $document = new XmlDocument('<settings><path>A</path><path>B</path></settings>');
        $document->write('/settings/path[2]', 'changed');
        $document->write('/settings/path[2]/@enabled', 'true');

        self::assertSame('A', $document->read('/settings/path[1]')->value);
        self::assertSame('changed', $document->read('/settings/path[2]')->value);
        self::assertSame('true', $document->read('/settings/path[2]/@enabled')->value);
    }

    /**
     * @throws DOMException
     */
    public function testAppendStepRejectsAnInvalidName(): void
    {
        $document = new XmlDocument('<settings/>');
        $parent = $document->dom()->documentElement;
        self::assertInstanceOf(DOMElement::class, $parent);
        $this->expectException(PolicyException::class);
        $document->appendStep($parent, '/bad:name');
    }

}
