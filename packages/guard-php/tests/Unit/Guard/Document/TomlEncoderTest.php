<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Document\TomlEncoder
 * @uses \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Document\TomlEncoder::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class TomlEncoderTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testEncodeRejectsUnrepresentableNull(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Document\TomlEncoder())->encode((object) ['x' => null]);
    }

    /**
     * @throws JsonException
     */
    public function testKeepsQuotedKeysAndLists(): void
    {
        $encoded = (new \Toolkit\Guard\Document\TomlEncoder())->encode((object) ['a.b' => ['A', 'B']]);
        self::assertSame(['a.b' => ['A', 'B']], \Yosymfony\Toml\Toml::parse($encoded));
    }

    /**
     * @throws JsonException
     */
    public function testValueKeepsBooleanAndNumericTypes(): void
    {
        $encoder = new \Toolkit\Guard\Document\TomlEncoder();
        self::assertSame('false', $encoder->value(false));
        self::assertSame('1.0', $encoder->value(1.0));
    }
    /**
     * @throws JsonException
     */
    public function testQuoteEscapesControlCharacters(): void
    {
        self::assertSame('"a\\nb"', (new \Toolkit\Guard\Document\TomlEncoder())->quote("a\nb"));
    }
}
