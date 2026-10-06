<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Doc;

use Guard\Collect\Markdown\Parsing\AtxHeadingMatcher;
use Guard\Collect\Markdown\Parsing\Heading;
use Guard\Collect\Markdown\Parsing\HeadingTextNormalizer;
use Guard\Config\Doc\ConfigKeyValidator;
use Guard\Config\Doc\DeclaredHeading;
use Guard\Config\Doc\DeclaredHeadingReader;
use Guard\Config\Doc\DocumentConfig;
use Guard\Config\Doc\DocumentConfigReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Doc\DocumentConfigReader
 * @uses \Guard\Collect\Markdown\Parsing\AtxHeadingMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Heading
 * @uses \Guard\Collect\Markdown\Parsing\HeadingTextNormalizer
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Doc\ConfigKeyValidator
 * @uses \Guard\Config\Doc\DeclaredHeading
 * @uses \Guard\Config\Doc\DeclaredHeadingReader
 * @uses \Guard\Config\Doc\DocumentConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(DocumentConfigReader::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
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
