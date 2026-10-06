<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy\Tree;

use Guard\Init\Legacy\Tree\ReportConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\Tree\ReportConfig
 */
#[CoversClass(ReportConfig::class)]
final class ReportConfigTest extends TestCase
{
    public function testStoresReportData(): void
    {
        $config = new ReportConfig('json', ['path', 'rule']);

        self::assertSame('json', $config->reporter);
        self::assertSame(['path', 'rule'], $config->orderBy);
    }
}
