<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\ReportConfig;

/**
 * @covers \Toolkit\LocGuard\Config\ReportConfig
 */
#[CoversClass(ReportConfig::class)]
final class ReportConfigTest extends TestCase
{
    public function testStoresReporterSettings(): void
    {
        $config = new ReportConfig('ai', ['path', 'line']);

        self::assertSame('ai', $config->reporter);
        self::assertSame(['path', 'line'], $config->orderBy);
    }
}
