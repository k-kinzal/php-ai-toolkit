<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\DeclaredHeadingReader;
use Guard\Config\Value\DeclaredHeading;
use Guard\Policy\PolicyException;
use Guard\Structure\Markdown\AtxHeadingMatcher;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\DeclaredHeadingReader
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Value\DeclaredHeading
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 * @uses \Guard\Structure\Markdown\AtxHeadingMatcher
 * @uses \Guard\Structure\Markdown\Heading
 * @uses \Guard\Structure\Markdown\HeadingTextNormalizer
 */
#[CoversClass(DeclaredHeadingReader::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingTextNormalizer::class)]
final class DeclaredHeadingReaderTest extends TestCase
{
    public function testReadParsesAtxNotation(): void
    {
        $heading = (new DeclaredHeadingReader())->read('##  Getting   Started ##', 'documents.README.md.headings[1]');

        self::assertSame(2, $heading->level);
        self::assertSame('Getting Started', $heading->text);
    }

    public function testReadRejectsUnquotedCommentValue(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"documents.README.md.headings[0]" must be a heading in ATX notation such as \'## Usage\'');

        (new DeclaredHeadingReader())->read(null, 'documents.README.md.headings[0]');
    }

    public function testReadRejectsTextWithoutMarker(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Quote the value');

        (new DeclaredHeadingReader())->read('Usage', 'documents.README.md.headings[0]');
    }
}
