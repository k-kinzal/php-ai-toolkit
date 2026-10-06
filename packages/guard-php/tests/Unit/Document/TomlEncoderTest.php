<?php

declare(strict_types=1);

namespace Tests\Unit\Document;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Document\TomlEncoder
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Document\TomlEncoder::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class TomlEncoderTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testEncodeRejectsUnrepresentableNull(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Document\TomlEncoder())->encode((object) ['x' => null]);
    }

    /**
     * @throws JsonException
     */
    public function testKeepsQuotedKeysAndLists(): void
    {
        $encoded = (new \Guard\Document\TomlEncoder())->encode((object) ['a.b' => ['A', 'B']]);
        self::assertSame(['a.b' => ['A', 'B']], \Yosymfony\Toml\Toml::parse($encoded));
    }

    /**
     * @throws JsonException
     */
    public function testValueKeepsBooleanAndNumericTypes(): void
    {
        $encoder = new \Guard\Document\TomlEncoder();
        self::assertSame('false', $encoder->value(false));
        self::assertSame('1.0', $encoder->value(1.0));
    }
    /**
     * @throws JsonException
     */
    public function testQuoteEscapesControlCharacters(): void
    {
        self::assertSame('"a\\nb"', (new \Guard\Document\TomlEncoder())->quote("a\nb"));
    }
}
