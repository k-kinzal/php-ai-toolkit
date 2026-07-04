<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use PhpAiToolkit\Shared\FormatMode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
