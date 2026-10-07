<?php

declare(strict_types=1);

namespace Tests\Unit\Structure;

use Guard\Structure\Text;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Text
 */
#[CoversClass(Text::class)]
final class TextTest extends TestCase
{
    public function testContentReturnsTheBytesUnchanged(): void
    {
        self::assertSame("@AGENTS.md\r\n", (new Text("@AGENTS.md\r\n"))->content());
    }
}
