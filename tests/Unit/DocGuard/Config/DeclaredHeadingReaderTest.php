<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Config\DeclaredHeading;
use Toolkit\DocGuard\Config\DeclaredHeadingReader;
use Toolkit\DocGuard\DocGuardException;
use Toolkit\DocGuard\Markdown\AtxHeadingMatcher;
use Toolkit\DocGuard\Markdown\Heading;
use Toolkit\DocGuard\Markdown\HeadingTextNormalizer;

/**
 * @covers \Toolkit\DocGuard\Config\DeclaredHeadingReader
 * @uses \Toolkit\DocGuard\Markdown\AtxHeadingMatcher
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Markdown\HeadingTextNormalizer
 */
#[CoversClass(DeclaredHeadingReader::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(DocGuardException::class)]
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
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('"documents.README.md.headings[0]" must be a heading in ATX notation such as \'## Usage\'');

        (new DeclaredHeadingReader())->read(null, 'documents.README.md.headings[0]');
    }

    public function testReadRejectsTextWithoutMarker(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('Quote the value');

        (new DeclaredHeadingReader())->read('Usage', 'documents.README.md.headings[0]');
    }
}
