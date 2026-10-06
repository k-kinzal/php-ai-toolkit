<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\DeclaredHeadingReader;
use Guard\Config\Reader\DocumentConfigReader;
use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Config\Value\DeclaredHeading;
use Guard\Config\Value\DocumentConfig;
use Guard\Policy\PolicyException;
use Guard\Structure\Markdown\AtxHeadingMatcher;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\DocumentConfigReader
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Reader\DeclaredHeadingReader
 * @uses \Guard\Config\Validation\HeadingConfigKeyValidator
 * @uses \Guard\Config\Value\DeclaredHeading
 * @uses \Guard\Config\Value\DocumentConfig
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
#[CoversClass(DocumentConfigReader::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(HeadingConfigKeyValidator::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingTextNormalizer::class)]
final class DocumentConfigReaderTest extends TestCase
{
    public function testReadParsesHeadingsAndMaxLevel(): void
    {
        $document = (new DocumentConfigReader())->read('README.md', ['headings' => ['# Tool', '## Usage'], 'max_level' => 2]);

        self::assertSame('README.md', $document->path);
        self::assertSame(['# Tool', '## Usage'], array_map(static fn (DeclaredHeading $heading): string => $heading->notation(), $document->headings));
        self::assertSame(2, $document->maxLevel);
    }

    public function testReadAcceptsEmptyHeadingsAndDefaultsMaxLevel(): void
    {
        $document = (new DocumentConfigReader())->read('CLAUDE.md', ['headings' => []]);

        self::assertSame([], $document->headings);
        self::assertSame(6, $document->maxLevel);
    }

    public function testReadRejectsNonMapping(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "documents.README.md" must be a mapping with a "headings" list.');

        (new DocumentConfigReader())->read('README.md', ['# Tool']);
    }

    public function testReadRejectsMissingHeadings(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"documents.README.md.headings" must be a list of headings');

        (new DocumentConfigReader())->read('README.md', ['max_level' => 2]);
    }

    public function testReadRejectsHeadingsMapping(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"documents.README.md.headings" must be a list of headings');

        (new DocumentConfigReader())->read('README.md', ['headings' => ['usage' => '## Usage']]);
    }

    public function testReadRejectsUnknownKey(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"documents.README.md" contains unsupported key "heading"');

        (new DocumentConfigReader())->read('README.md', ['heading' => ['# Tool']]);
    }

    public function testReadRejectsInvalidMaxLevel(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"documents.README.md.max_level" must be an integer from 1 to 6.');

        (new DocumentConfigReader())->read('README.md', ['headings' => [], 'max_level' => 7]);
    }

    public function testReadRejectsHeadingDeeperThanMaxLevel(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"documents.README.md.headings[1]" declares "### Detail", which is deeper than "documents.README.md.max_level" (2).');

        (new DocumentConfigReader())->read('README.md', ['headings' => ['# Tool', '### Detail'], 'max_level' => 2]);
    }
}
