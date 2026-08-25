<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Reporting\Reporter;

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
