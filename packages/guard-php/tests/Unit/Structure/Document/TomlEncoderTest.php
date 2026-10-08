<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
#[CoversClass(\Guard\Structure\Document\TomlEncoder::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(\Guard\Diagnostic\PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
final class TomlEncoderTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testEncodeRejectsUnrepresentableNull(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        (new \Guard\Structure\Document\TomlEncoder())->encode((object) ['x' => null]);
    }

    /**
     * @throws JsonException
     */
    public function testKeepsQuotedKeysAndLists(): void
    {
        $encoded = (new \Guard\Structure\Document\TomlEncoder())->encode((object) ['a.b' => ['A', 'B']]);
        self::assertSame(['a.b' => ['A', 'B']], \Yosymfony\Toml\Toml::parse($encoded));
    }

    /**
     * @throws JsonException
     */
    public function testValueKeepsBooleanAndNumericTypes(): void
    {
        $encoder = new \Guard\Structure\Document\TomlEncoder();
        self::assertSame('false', $encoder->value(false));
        self::assertSame('1.0', $encoder->value(1.0));
    }
    /**
     * @throws JsonException
     */
    public function testQuoteEscapesControlCharacters(): void
    {
        self::assertSame('"a\\nb"', (new \Guard\Structure\Document\TomlEncoder())->quote("a\nb"));
    }
}
