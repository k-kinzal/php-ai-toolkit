<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Config;

use PhpAiToolkit\Doctest\Config\ReportConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReportConfig::class)]
final class ReportConfigTest extends TestCase
{
    public function testExposesTheReporterAndOrdering(): void
    {
        $config = new ReportConfig('json', ['path', 'line']);

        self::assertSame('json', $config->reporter);
        self::assertSame(['path', 'line'], $config->orderBy);
    }
}
