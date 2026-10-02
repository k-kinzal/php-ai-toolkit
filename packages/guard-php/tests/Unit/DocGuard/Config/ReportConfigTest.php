<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Config\ReportConfig;

/**
 * @covers \Toolkit\DocGuard\Config\ReportConfig
 */
#[CoversClass(ReportConfig::class)]
final class ReportConfigTest extends TestCase
{
    public function testGetExposesReporterAndOrder(): void
    {
        $config = new ReportConfig('json', ['rule', 'path']);

        self::assertSame('json', $config->reporter);
        self::assertSame(['rule', 'path'], $config->orderBy);
    }
}
