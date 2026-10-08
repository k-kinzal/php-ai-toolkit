<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\DeclaredHeadingReader;
use Guard\Config\Reader\DocumentConfigReader;
use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Definition\DeclaredHeading;
use Guard\Policy\Definition\DocumentConfig;
use Guard\Structure\Markdown\AtxHeadingMatcher;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\DocumentConfigReader
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Reader\BadgeConfigReader
 * @uses \Guard\Config\Reader\DeclaredHeadingReader
 * @uses \Guard\Config\Reader\OutlineConfigReader
 * @uses \Guard\Config\Validation\HeadingConfigKeyValidator
 * @uses \Guard\Policy\Definition\BadgeEntry
 * @uses \Guard\Policy\Definition\DeclaredHeading
 * @uses \Guard\Policy\Definition\DocumentConfig
 * @uses \Guard\Policy\Definition\OutlineEntry
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Structure\Markdown\AtxHeadingMatcher
 * @uses \Guard\Structure\Markdown\Heading
 * @uses \Guard\Structure\Markdown\HeadingTextNormalizer
 */
#[CoversClass(DocumentConfigReader::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(HeadingConfigKeyValidator::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(\Guard\Config\Reader\BadgeConfigReader::class)]
#[UsesClass(\Guard\Config\Reader\OutlineConfigReader::class)]
#[UsesClass(\Guard\Policy\Definition\BadgeEntry::class)]
#[UsesClass(\Guard\Policy\Definition\OutlineEntry::class)]
final class DocumentConfigReaderTest extends TestCase
{
    public function testReadParsesHeadingsAndMaxLevel(): void
    {
        $document = (new DocumentConfigReader())->read('README.md', ['headings' => ['# Tool', '## Usage'], 'max_level' => 2]);

        self::assertSame('README.md', $document->path);
        self::assertNotNull($document->headings);
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

    public function testReadAcceptsOutlinesBadgesAndContentWithoutHeadings(): void
    {
        $document = (new DocumentConfigReader())->read('README.md', [
            'outlines' => ['package' => ['# *', '## License']],
            'badges' => [['name' => 'PHP', 'image' => 'https://img.shields.io/badge/php-*']],
            'content' => '',
        ]);

        self::assertNull($document->headings);
        self::assertSame(['package'], array_keys($document->outlines));
        self::assertNotNull($document->badges);
        self::assertSame('PHP', $document->badges[0]->name);
        self::assertSame('', $document->content);
    }

    public function testReadKeepsExactHeadingsNextToAnOutline(): void
    {
        $document = (new DocumentConfigReader())->read('AGENTS.md', ['headings' => ['# AGENTS'], 'outlines' => ['agents' => ['# AGENTS']]]);

        self::assertNotNull($document->headings);
        self::assertCount(1, $document->headings);
        self::assertCount(1, $document->outlines['agents']);
        self::assertNull($document->badges);
        self::assertNull($document->content);
    }

    public function testHeadingsReadsAListNoDeeperThanTheMaximum(): void
    {
        $headings = (new DocumentConfigReader())->headings(['# Tool', '## Usage'], 2, 'documents.README.md');

        self::assertSame(['# Tool', '## Usage'], array_map(static fn (DeclaredHeading $heading): string => $heading->notation(), $headings));
    }

    public function testReadRejectsNonStringContent(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"documents.CLAUDE.md.content" must be a string holding the exact file content.');

        (new DocumentConfigReader())->read('CLAUDE.md', ['content' => ['@AGENTS.md']]);
    }

    public function testReadRejectsHeadingDeeperThanMaxLevel(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"documents.README.md.headings[1]" declares "### Detail", which is deeper than "documents.README.md.max_level" (2).');

        (new DocumentConfigReader())->read('README.md', ['headings' => ['# Tool', '### Detail'], 'max_level' => 2]);
    }
}
