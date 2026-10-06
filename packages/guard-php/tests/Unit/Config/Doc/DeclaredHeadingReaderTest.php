<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Doc;

use Guard\Collect\Markdown\Parsing\AtxHeadingMatcher;
use Guard\Collect\Markdown\Parsing\Heading;
use Guard\Collect\Markdown\Parsing\HeadingTextNormalizer;
use Guard\Config\Doc\DeclaredHeading;
use Guard\Config\Doc\DeclaredHeadingReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Doc\DeclaredHeadingReader
 * @uses \Guard\Collect\Markdown\Parsing\AtxHeadingMatcher
 * @uses \Guard\Collect\Markdown\Parsing\Heading
 * @uses \Guard\Collect\Markdown\Parsing\HeadingTextNormalizer
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Doc\DeclaredHeading
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(DeclaredHeadingReader::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingTextNormalizer::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
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
