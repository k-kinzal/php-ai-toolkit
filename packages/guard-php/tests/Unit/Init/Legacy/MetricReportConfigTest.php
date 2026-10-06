<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy;

use Guard\Init\Legacy\MetricReportConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\MetricReportConfig
 */
#[CoversClass(MetricReportConfig::class)]
final class MetricReportConfigTest extends TestCase
{
    public function testStoresReporterSettings(): void
    {
        $config = new MetricReportConfig('ai', ['path', 'line']);

        self::assertSame('ai', $config->reporter);
        self::assertSame(['path', 'line'], $config->orderBy);
    }
}
