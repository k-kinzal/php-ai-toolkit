<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Reporting\Reporter;

/**
 * @coversNothing
 */
#[CoversNothing]
final class ReporterTest extends TestCase
{
    public function testReporterInterfaceExists(): void
    {
        self::assertTrue(interface_exists(Reporter::class));
    }
}
