<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use Guard\Config\Configuration;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\DirectoryListing;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Context;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use Guard\Structure\Document\TomlEncoder;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Yosymfony\Toml\Toml;

/**
 * @covers \Guard\Structure\Document\TomlEncoder
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(TomlEncoder::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
final class TomlEncoderTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testEncodeRejectsUnrepresentableNull(): void
    {
        $this->expectException(PolicyException::class);
        (new TomlEncoder())->encode((object) ['x' => null]);
    }

    /**
     * @throws JsonException
     */
    public function testKeepsQuotedKeysAndLists(): void
    {
        $encoded = (new TomlEncoder())->encode((object) ['a.b' => ['A', 'B']]);
        self::assertSame(['a.b' => ['A', 'B']], Toml::parse($encoded));
    }

    /**
     * @throws JsonException
     */
    public function testValueKeepsBooleanAndNumericTypes(): void
    {
        $encoder = new TomlEncoder();
        self::assertSame('false', $encoder->value(false));
        self::assertSame('1.0', $encoder->value(1.0));
    }
    /**
     * @throws JsonException
     */
    public function testQuoteEscapesControlCharacters(): void
    {
        self::assertSame('"a\\nb"', (new TomlEncoder())->quote("a\nb"));
    }
}
