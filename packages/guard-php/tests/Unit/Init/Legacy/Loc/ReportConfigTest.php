<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy\Loc;

use Guard\Init\Legacy\Loc\ReportConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\Loc\ReportConfig
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
