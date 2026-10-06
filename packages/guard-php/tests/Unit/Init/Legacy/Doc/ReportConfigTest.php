<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy\Doc;

use Guard\Init\Legacy\Doc\ReportConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\Doc\ReportConfig
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
