<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Cli\LocGuardHelpText;

/**
 * @covers \Toolkit\LocGuard\Cli\LocGuardHelpText
 */
#[CoversClass(LocGuardHelpText::class)]
final class LocGuardHelpTextTest extends TestCase
{
    public function testTextReturnsUsageText(): void
    {
        self::assertStringContainsString('Usage:', (new LocGuardHelpText())->text());
    }
}
