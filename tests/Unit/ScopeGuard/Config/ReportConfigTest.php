<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Config;

use PhpAiToolkit\ScopeGuard\Config\ReportConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
