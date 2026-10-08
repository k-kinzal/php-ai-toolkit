<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project\Legacy;

use Guard\Config\Project\Legacy\MetricReportConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Project\Legacy\MetricReportConfig
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
