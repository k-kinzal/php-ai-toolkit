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
use Toolkit\DocGuard\Config\DocumentListConfigReader;
use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Filesystem\DocGuardPathResolver;
use Toolkit\DocGuard\Markdown\AtxHeadingMatcher;
use Toolkit\DocGuard\Markdown\Heading;
use Toolkit\DocGuard\Markdown\HeadingTextNormalizer;

/**
 * @covers \Toolkit\DocGuard\Config\DocumentListConfigReader
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Config\DeclaredHeadingReader
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Filesystem\DocGuardPathResolver
 * @uses \Toolkit\DocGuard\Config\DocumentConfig
 * @uses \Toolkit\DocGuard\Config\DocumentConfigReader
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 */
#[CoversClass(DocumentListConfigReader::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(DocGuardException::class)]
#[UsesClass(DocGuardPathResolver::class)]
#[UsesClass(DocumentConfig::class)]
#[UsesClass(DocumentConfigReader::class)]
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
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "documents" must be a non-empty mapping from document paths to their declared structure.');

        (new DocumentListConfigReader())->read([]);
    }

    public function testReadRejectsListOfDocuments(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('"documents" must be keyed by document paths such as README.md, found "0".');

        (new DocumentListConfigReader())->read([['headings' => []]]);
    }

    public function testReadRejectsDuplicateDocuments(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('"documents" declares README.md more than once.');

        (new DocumentListConfigReader())->read(['README.md' => ['headings' => []], './README.md' => ['headings' => []]]);
    }
}
