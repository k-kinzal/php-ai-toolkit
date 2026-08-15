<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse;

use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
