<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\Shared\FormatMode;

/**
 * @covers \Toolkit\Shared\FormatMode
 */
#[CoversClass(FormatMode::class)]
final class FormatModeTest extends TestCase
{
    public function testConstantValues(): void
    {
        self::assertSame('auto', FormatMode::AUTO);
        self::assertSame('ai', FormatMode::AI);
        self::assertSame('human', FormatMode::HUMAN);
    }
}
