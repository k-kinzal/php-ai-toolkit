<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Doc;

use Guard\Collect\Markdown\Filesystem\PathResolver;
use Guard\Collect\Markdown\Parsing\AtxHeadingMatcher;
use Guard\Collect\Markdown\Parsing\Heading;
use Guard\Collect\Markdown\Parsing\HeadingTextNormalizer;
use Guard\Config\Doc\ConfigKeyValidator;
use Guard\Config\Doc\DeclaredHeading;
use Guard\Config\Doc\DeclaredHeadingReader;
use Guard\Config\Doc\DocumentConfig;
use Guard\Config\Doc\DocumentConfigReader;
use Guard\Config\Doc\DocumentListConfigReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Doc\DocumentListConfigReader
 * @uses \Guard\Collect\Markdown\Filesystem\PathResolver
 * @uses \Guard\Collect\Markdown\Parsing\AtxHeadingMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Heading
 * @uses \Guard\Collect\Markdown\Parsing\HeadingTextNormalizer
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Doc\ConfigKeyValidator
 * @uses \Guard\Config\Doc\DeclaredHeading
 * @uses \Guard\Config\Doc\DeclaredHeadingReader
 * @uses \Guard\Config\Doc\DocumentConfig
 * @uses \Guard\Config\Doc\DocumentConfigReader
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(DocumentListConfigReader::class)]
#[UsesClass(PathResolver::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(DocumentConfigReader::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
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
