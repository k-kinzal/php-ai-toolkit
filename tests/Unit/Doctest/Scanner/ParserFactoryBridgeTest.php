<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Scanner;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Scanner\ParserFactoryBridge;

/**
 * @covers \Toolkit\Doctest\Scanner\ParserFactoryBridge
 */
#[CoversClass(ParserFactoryBridge::class)]
final class ParserFactoryBridgeTest extends TestCase
{
    public function testCreateReturnsAParserThatParsesSource(): void
    {
        $parser = (new ParserFactoryBridge())->create();

        self::assertCount(1, (array) $parser->parse('<?php $value = 1;'));
    }
}
