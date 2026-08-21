<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Config;

use PhpAiToolkit\Doctest\Config\ConfigScalarReader;
use PhpAiToolkit\Doctest\Config\ConfigStringListReader;
use PhpAiToolkit\Doctest\Config\ReportConfig;
use PhpAiToolkit\Doctest\Config\ReportConfigReader;
use PhpAiToolkit\Doctest\DoctestException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReportConfigReader::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
final class ReportConfigReaderTest extends TestCase
{
    public function testReadAppliesDefaultsForAnAbsentSection(): void
    {
        $config = (new ReportConfigReader())->read([]);

        self::assertSame('ai', $config->reporter);
        self::assertSame(['path', 'line'], $config->orderBy);
    }

    public function testReadAcceptsTheSupportedReportersAndFields(): void
    {
        $config = (new ReportConfigReader())->read(['reporter' => 'json', 'order_by' => ['symbol']]);

        self::assertSame('json', $config->reporter);
        self::assertSame(['symbol'], $config->orderBy);
    }

    public function testReadRejectsASectionThatIsNotAMapping(): void
    {
        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('Invalid doctest.yaml: "report" must be a mapping.');

        (new ReportConfigReader())->read('ai');
    }

    public function testReadRejectsAnUnsupportedReporter(): void
    {
        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('Invalid doctest.yaml: "report.reporter" must be one of: ai, text, json.');

        (new ReportConfigReader())->read(['reporter' => 'xml']);
    }

    public function testReadRejectsAnUnsupportedOrderField(): void
    {
        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('Invalid doctest.yaml: "report.order_by" contains unsupported field "size".');

        (new ReportConfigReader())->read(['order_by' => ['size']]);
    }
}
