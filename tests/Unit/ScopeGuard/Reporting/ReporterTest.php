<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PhpAiToolkit\ScopeGuard\Reporting\Reporter;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class ReporterTest extends TestCase
{
    public function testReporterInterfaceExists(): void
    {
        self::assertTrue(interface_exists(Reporter::class));
    }
}
