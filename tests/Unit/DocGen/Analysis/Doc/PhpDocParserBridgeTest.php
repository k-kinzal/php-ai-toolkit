<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Doc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;

/**
 * @covers \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 */
#[CoversClass(PhpDocParserBridge::class)]
final class PhpDocParserBridgeTest extends TestCase
{
    public function testParseFindsTagsInTheParsedNode(): void
    {
        $node = (new PhpDocParserBridge())->parse('/** @param int $x */');

        self::assertCount(1, $node->getTagsByName('@param'));
    }

    public function testParseSupportsRepeatedCallsOnOneBridge(): void
    {
        $bridge = new PhpDocParserBridge();

        $first = $bridge->parse('/** @param int $x */');
        $second = $bridge->parse('/** @return string */');

        self::assertCount(1, $first->getTagsByName('@param'));
        self::assertCount(1, $second->getTagsByName('@return'));
    }

    public function testConfigArgumentsReturnsAtMostOneLeadingArgument(): void
    {
        $arguments = (new PhpDocParserBridge())->configArguments();

        self::assertTrue(count($arguments) <= 1);
    }
}
