<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Config\ConfigKeyValidator;
use Toolkit\DocGuard\Config\ConfigStringListReader;
use Toolkit\DocGuard\Config\ReportConfig;
use Toolkit\DocGuard\Config\ReportConfigReader;
use Toolkit\DocGuard\DocGuardException;

/**
 * @covers \Toolkit\DocGuard\Config\ReportConfigReader
 * @uses \Toolkit\DocGuard\Config\ConfigKeyValidator
 * @uses \Toolkit\DocGuard\Config\ConfigStringListReader
 * @uses \Toolkit\DocGuard\DocGuardException
 * @uses \Toolkit\DocGuard\Config\ReportConfig
 */
#[CoversClass(ReportConfigReader::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(DocGuardException::class)]
#[UsesClass(ReportConfig::class)]
final class ReportConfigReaderTest extends TestCase
{
    public function testReadAppliesDefaults(): void
    {
        $config = (new ReportConfigReader())->read([]);

        self::assertSame('ai', $config->reporter);
        self::assertSame(['path', 'line', 'rule'], $config->orderBy);
    }

    public function testReadParsesReporterAndOrder(): void
    {
        $config = (new ReportConfigReader())->read(['reporter' => 'text', 'order_by' => ['rule']]);

        self::assertSame('text', $config->reporter);
        self::assertSame(['rule'], $config->orderBy);
    }

    public function testReadRejectsNonMapping(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "report" must be a mapping.');

        (new ReportConfigReader())->read('ai');
    }

    public function testReadRejectsUnknownReporter(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('"report.reporter" must be one of: ai, text, json.');

        (new ReportConfigReader())->read(['reporter' => 'xml']);
    }

    public function testReadRejectsUnknownOrderField(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('"report.order_by" contains unsupported field "limit". Supported fields: path, line, rule.');

        (new ReportConfigReader())->read(['order_by' => ['limit']]);
    }

    public function testReadRejectsUnknownKey(): void
    {
        $this->expectException(DocGuardException::class);
        $this->expectExceptionMessage('"report" contains unsupported key "orderBy"');

        (new ReportConfigReader())->read(['orderBy' => ['path']]);
    }
}
