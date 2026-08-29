<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Cli\LocGuardHelpText;

/**
 * @coversNothing
 */
#[CoversNothing]
final class LocGuardHelpTextTest extends TestCase
{
    public function testTextReturnsUsageText(): void
    {
        $text = (new LocGuardHelpText())->text();

        self::assertStringContainsString('Usage:', $text);
        self::assertStringContainsString('--explain PATH', $text);
    }
}
