<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project\Legacy;

use Guard\Config\Project\Legacy\DirectoryReportConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Project\Legacy\DirectoryReportConfig
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
