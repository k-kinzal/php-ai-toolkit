<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Config\ConfigKeyValidator;
use Toolkit\DocGuard\Config\DeclaredHeading;
use Toolkit\DocGuard\Config\DeclaredHeadingReader;
use Toolkit\DocGuard\Config\DocumentConfig;
use Toolkit\DocGuard\Config\DocumentConfigReader;
use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Markdown\AtxHeadingMatcher;
use Toolkit\DocGuard\Markdown\Heading;
use Toolkit\DocGuard\Markdown\HeadingTextNormalizer;

/**
 * @covers \Toolkit\DocGuard\Config\DocumentConfigReader
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Config\DeclaredHeadingReader
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 */
#[CoversClass(DocumentConfigReader::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(DocGuardException::class)]
#[UsesClass(DocumentConfig::class)]
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
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "documents.README.md" must be a mapping with a "headings" list.');

        (new DocumentConfigReader())->read('README.md', ['# Tool']);
    }

    public function testReadRejectsMissingHeadings(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('"documents.README.md.headings" must be a list of headings');

        (new DocumentConfigReader())->read('README.md', ['max_level' => 2]);
    }

    public function testReadRejectsHeadingsMapping(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('"documents.README.md.headings" must be a list of headings');

        (new DocumentConfigReader())->read('README.md', ['headings' => ['usage' => '## Usage']]);
    }

    public function testReadRejectsUnknownKey(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('"documents.README.md" contains unsupported key "heading"');

        (new DocumentConfigReader())->read('README.md', ['heading' => ['# Tool']]);
    }

    public function testReadRejectsInvalidMaxLevel(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('"documents.README.md.max_level" must be an integer from 1 to 6.');

        (new DocumentConfigReader())->read('README.md', ['headings' => [], 'max_level' => 7]);
    }

    public function testReadRejectsHeadingDeeperThanMaxLevel(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('"documents.README.md.headings[1]" declares "### Detail", which is deeper than "documents.README.md.max_level" (2).');

        (new DocumentConfigReader())->read('README.md', ['headings' => ['# Tool', '### Detail'], 'max_level' => 2]);
    }
}
