<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\ConfigKeyValidator;
use Toolkit\LocGuard\Config\ConfigScalarReader;
use Toolkit\LocGuard\Config\ConfigStringListReader;
use Toolkit\LocGuard\Config\ReportConfig;
use Toolkit\LocGuard\Config\ReportConfigReader;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\Config\ReportConfigReader
 * @uses \Toolkit\LocGuard\Config\ConfigScalarReader
 * @uses \Toolkit\LocGuard\Config\ConfigStringListReader
 * @uses \Toolkit\LocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\LocGuard\Config\ReportConfig
 */
#[CoversClass(ReportConfigReader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ReportConfig::class)]
final class ReportConfigReaderTest extends TestCase
{
    public function testReadReturnsReportConfig(): void
    {
        $report = (new ReportConfigReader())->read(['reporter' => 'json', 'order_by' => ['rule']]);

        self::assertSame('json', $report->reporter);
        self::assertSame(['rule'], $report->orderBy);
    }

    public function testReadRejectsUnsupportedReporter(): void
    {
        $this->expectException(LocGuardException::class);

        (new ReportConfigReader())->read(['reporter' => 'xml']);
    }

    public function testReadRejectsUnsupportedOrderField(): void
    {
        $this->expectException(LocGuardException::class);

        (new ReportConfigReader())->read(['order_by' => ['severity']]);
    }
}
