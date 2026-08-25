<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Config\ReportConfig;

/**
 * @covers \Toolkit\ScopeGuard\Config\ReportConfig
 */
#[CoversClass(ReportConfig::class)]
final class ReportConfigTest extends TestCase
{
    public function testReporterIsReadable(): void
    {
        self::assertSame('json', (new ReportConfig('json', ['path']))->reporter);
    }

    public function testOrderByIsReadable(): void
    {
        self::assertSame(['path'], (new ReportConfig('json', ['path']))->orderBy);
    }
}
