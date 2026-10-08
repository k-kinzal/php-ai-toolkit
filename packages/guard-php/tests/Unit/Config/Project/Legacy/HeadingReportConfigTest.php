<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project\Legacy;

use Guard\Config\Project\Legacy\HeadingReportConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Project\Legacy\HeadingReportConfig
 */
#[CoversClass(HeadingReportConfig::class)]
final class HeadingReportConfigTest extends TestCase
{
    public function testGetExposesReporterAndOrder(): void
    {
        $config = new HeadingReportConfig('json', ['rule', 'path']);

        self::assertSame('json', $config->reporter);
        self::assertSame(['rule', 'path'], $config->orderBy);
    }
}
