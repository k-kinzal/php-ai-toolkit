<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Analysis;

use PhpAiToolkit\Doctest\Analysis\PhpParserBridge;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpParserBridge::class)]
final class PhpParserBridgeTest extends TestCase
{
    public function testParserParsesSourceAndIsMemoized(): void
    {
        $bridge = new PhpParserBridge();
        $parser = $bridge->parser();

        self::assertCount(1, (array) $parser->parse('<?php $value = 1;'));
        self::assertSame($parser, $bridge->parser());
    }
}
