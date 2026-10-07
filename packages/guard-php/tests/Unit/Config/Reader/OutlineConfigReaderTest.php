<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Reader;

use Guard\Config\Reader\DeclaredHeadingReader;
use Guard\Config\Reader\OutlineConfigReader;
use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Config\Value\DeclaredHeading;
use Guard\Config\Value\OutlineEntry;
use Guard\Policy\PolicyException;
use Guard\Structure\Markdown\AtxHeadingMatcher;
use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingTextNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Reader\OutlineConfigReader
 * @uses \Guard\Config\Reader\DeclaredHeadingReader
 * @uses \Guard\Config\Validation\HeadingConfigKeyValidator
 * @uses \Guard\Config\Value\DeclaredHeading
 * @uses \Guard\Config\Value\OutlineEntry
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Structure\Markdown\AtxHeadingMatcher
 * @uses \Guard\Structure\Markdown\Heading
 * @uses \Guard\Structure\Markdown\HeadingTextNormalizer
 */
#[CoversClass(OutlineConfigReader::class)]
#[UsesClass(DeclaredHeadingReader::class)]
#[UsesClass(HeadingConfigKeyValidator::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(OutlineEntry::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(AtxHeadingMatcher::class)]
#[UsesClass(Heading::class)]
#[UsesClass(HeadingTextNormalizer::class)]
final class OutlineConfigReaderTest extends TestCase
{
    public function testReadKeepsOutlineNamesAndEntryFlags(): void
    {
        $outlines = (new OutlineConfigReader())->read([
            'package' => ['# *', ['heading' => '## *', 'optional' => true, 'repeat' => true], '## License'],
            'monorepo' => ['# *', '## Packages'],
        ], 'documents.README.md');

        self::assertSame(['package', 'monorepo'], array_keys($outlines));
        self::assertSame(['# *', '## *', '## License'], array_map(static fn (OutlineEntry $entry): string => $entry->notation(), $outlines['package']));
        self::assertTrue($outlines['package'][1]->optional);
        self::assertTrue($outlines['package'][1]->repeat);
        self::assertFalse($outlines['package'][2]->optional);
    }

    public function testReadRejectsAList(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"documents.README.md.outlines" must be a non-empty mapping from outline names to heading lists');

        (new OutlineConfigReader())->read(['# *'], 'documents.README.md');
    }

    public function testReadRejectsAnEmptyOutline(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"documents.README.md.outlines.package" must be a non-empty list of headings');

        (new OutlineConfigReader())->read(['package' => []], 'documents.README.md');
    }

    public function testEntryRejectsUnknownKeys(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"documents.README.md.outlines.package[0]" contains unsupported key "required"');

        (new OutlineConfigReader())->entry(['heading' => '## Usage', 'required' => true], 'documents.README.md.outlines.package[0]');
    }

    public function testEntryRejectsNonBooleanFlags(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"entry.optional" and "entry.repeat" must be true or false.');

        (new OutlineConfigReader())->entry(['heading' => '## Usage', 'optional' => 'yes'], 'entry');
    }

    public function testEntryRequiresExactlyOneOfHeadingAndOneOf(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"entry" must set exactly one of "heading" and "one_of".');

        (new OutlineConfigReader())->entry(['optional' => true], 'entry');
    }

    public function testEntryRejectsBothHeadingAndOneOf(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"entry" must set exactly one of "heading" and "one_of".');

        (new OutlineConfigReader())->entry(['heading' => '## Vision', 'one_of' => ['## Vision']], 'entry');
    }

    public function testEntryReadsOneOfAsAlternatives(): void
    {
        $entry = (new OutlineConfigReader())->entry(['one_of' => ['## Vision', '## Product Vision'], 'optional' => true], 'entry');

        self::assertSame(['## Vision', '## Product Vision'], $entry->notations());
        self::assertTrue($entry->optional);
    }

    public function testAlternativesRejectsAnEmptyList(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"entry.one_of" must be a non-empty list of headings');

        (new OutlineConfigReader())->alternatives([], 'entry.one_of');
    }

    public function testAlternativesRejectsAWildcard(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"entry.one_of[1]" cannot be a wildcard.');

        (new OutlineConfigReader())->alternatives(['## Vision', '## *'], 'entry.one_of');
    }
}
