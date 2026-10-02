<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Parse\PhpParserBridge;

/**
 * @covers \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 */
#[CoversClass(PhpParserBridge::class)]
final class PhpParserBridgeTest extends TestCase
{
    public function testParserParsesSimpleSource(): void
    {
        $statements = (new PhpParserBridge())->parser()->parse('<?php echo 1;');

        self::assertNotNull($statements);
        self::assertCount(1, $statements);
    }

    public function testParserReturnsMemoizedInstance(): void
    {
        $bridge = new PhpParserBridge();

        self::assertSame($bridge->parser(), $bridge->parser());
    }
}
