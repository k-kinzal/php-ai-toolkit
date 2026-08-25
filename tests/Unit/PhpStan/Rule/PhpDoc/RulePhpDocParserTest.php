<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PhpAiToolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser
 */
#[CoversClass(RulePhpDocParser::class)]
final class RulePhpDocParserTest extends TestCase
{
    public function testParseFindsReturnTags(): void
    {
        $node = (new RulePhpDocParser())->parse('/** @return array<string, mixed> */');

        self::assertCount(1, $node->getTagsByName('@return'));
    }

    public function testParseSupportsRepeatedCalls(): void
    {
        $parser = new RulePhpDocParser();

        self::assertCount(1, $parser->parse('/** @return string */')->getTagsByName('@return'));
        self::assertCount(1, $parser->parse('/** @phpstan-return int */')->getTagsByName('@phpstan-return'));
    }

    public function testConfigArgumentsReturnsAtMostOneLeadingArgument(): void
    {
        self::assertTrue(count((new RulePhpDocParser())->configArguments()) <= 1);
    }
}
