<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Cli;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Cli\TreeGuardHelpText;

/**
 * @coversNothing
 */
#[CoversNothing]
final class TreeGuardHelpTextTest extends TestCase
{
    public function testTextDescribesUsageAndOptions(): void
    {
        $text = (new TreeGuardHelpText())->text();

        self::assertStringContainsString('tree-guard checks directory and file structure constraints.', $text);
        self::assertStringContainsString('Usage:', $text);
        self::assertStringContainsString('--config PATH', $text);
        self::assertStringContainsString('--reporter NAME', $text);
        self::assertStringContainsString('--version, -V', $text);
    }
}
