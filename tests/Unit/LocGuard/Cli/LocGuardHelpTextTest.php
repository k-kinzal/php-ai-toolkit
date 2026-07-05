<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PhpAiToolkit\LocGuard\Cli\LocGuardHelpText;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocGuardHelpText::class)]
final class LocGuardHelpTextTest extends TestCase
{
    public function testTextReturnsUsageText(): void
    {
        self::assertStringContainsString('Usage:', (new LocGuardHelpText())->text());
    }
}
