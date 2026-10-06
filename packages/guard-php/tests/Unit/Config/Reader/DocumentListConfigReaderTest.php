<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Collect\Filesystem\Path as PathResolver;
use Guard\Config\Reader\DeclaredHeadingReader;
use Guard\Config\Reader\DocumentConfigReader;
use Guard\Config\Reader\DocumentListConfigReader;
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
 * @covers \Guard\Config\Reader\DocumentListConfigReader
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Filesystem\Path
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Reader\DeclaredHeadingReader
 * @uses \Guard\Config\Reader\DocumentConfigReader
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
#[CoversClass(DocumentListConfigReader::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(PathResolver::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(DocumentConfigReader::class)]
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
