<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy;

use Guard\Init\Legacy\DirectoryReportConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\DirectoryReportConfig
 */
#[CoversClass(DirectoryReportConfig::class)]
final class DirectoryReportConfigTest extends TestCase
{
    public function testStoresReportData(): void
    {
        $config = new DirectoryReportConfig('json', ['path', 'rule']);

        self::assertSame('json', $config->reporter);
        self::assertSame(['path', 'rule'], $config->orderBy);
    }
}
