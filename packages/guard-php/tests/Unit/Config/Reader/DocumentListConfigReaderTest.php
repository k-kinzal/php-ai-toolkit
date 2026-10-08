<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\DeclaredHeadingReader;
use Guard\Config\Reader\DocumentConfigReader;
use Guard\Config\Reader\DocumentListConfigReader;
use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Diagnostic\PolicyException;
use Guard\Input\Path as PathResolver;
use Guard\Policy\Definition\DeclaredHeading;
use Guard\Policy\Definition\DocumentConfig;
use Guard\Structure\Markdown\AtxHeadingMatcher;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\DocumentListConfigReader
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Path
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Reader\DeclaredHeadingReader
 * @uses \Guard\Config\Reader\DocumentConfigReader
 * @uses \Guard\Config\Validation\HeadingConfigKeyValidator
 * @uses \Guard\Policy\Definition\DeclaredHeading
 * @uses \Guard\Policy\Definition\DocumentConfig
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
#[CoversClass(DocumentListConfigReader::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(PathResolver::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(DocumentConfigReader::class)]
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
final class DocumentListConfigReaderTest extends TestCase
{
    public function testReadReturnsDocumentsWithNormalizedPaths(): void
    {
        $documents = (new DocumentListConfigReader())->read([
            './README.md' => ['headings' => ['# Tool']],
            'docs//guide.md' => ['headings' => []],
        ]);

        self::assertSame(['README.md', 'docs/guide.md'], array_map(static fn (DocumentConfig $document): string => $document->path, $documents));
    }

    public function testReadRejectsMissingOrEmptySection(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "documents" must be a non-empty mapping from document paths to their declared structure.');

        (new DocumentListConfigReader())->read([]);
    }

    public function testReadRejectsListOfDocuments(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"documents" must be keyed by document paths such as README.md, found "0".');

        (new DocumentListConfigReader())->read([['headings' => []]]);
    }

    public function testReadRejectsDuplicateDocuments(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"documents" declares README.md more than once.');

        (new DocumentListConfigReader())->read(['README.md' => ['headings' => []], './README.md' => ['headings' => []]]);
    }
}
