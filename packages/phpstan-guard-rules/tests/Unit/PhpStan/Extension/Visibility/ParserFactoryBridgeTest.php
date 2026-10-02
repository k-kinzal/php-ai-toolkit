<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Extension\Visibility;

use PhpParser\Parser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Extension\Visibility\ParserFactoryBridge;

/**
 * @covers \Toolkit\PhpStan\Extension\Visibility\ParserFactoryBridge
 */
#[CoversClass(ParserFactoryBridge::class)]
final class ParserFactoryBridgeTest extends TestCase
{
    public function testParserSupportsTheInstalledPhpParserMajor(): void
    {
        self::assertInstanceOf(Parser::class, (new ParserFactoryBridge())->parser());
    }
}
