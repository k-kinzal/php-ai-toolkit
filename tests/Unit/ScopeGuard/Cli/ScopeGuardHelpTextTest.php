<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Cli\ScopeGuardHelpText;

/**
 * @covers \Toolkit\ScopeGuard\Cli\ScopeGuardHelpText
 */
#[CoversClass(ScopeGuardHelpText::class)]
final class ScopeGuardHelpTextTest extends TestCase
{
    public function testTextNamesTheCommand(): void
    {
        self::assertStringStartsWith('scope-guard checks', (new ScopeGuardHelpText())->text());
    }

    public function testTextDocumentsTheConfigOption(): void
    {
        self::assertStringContainsString('--config PATH', (new ScopeGuardHelpText())->text());
    }

    public function testTextDocumentsTheReporterOption(): void
    {
        self::assertStringContainsString('--reporter NAME', (new ScopeGuardHelpText())->text());
    }
}
