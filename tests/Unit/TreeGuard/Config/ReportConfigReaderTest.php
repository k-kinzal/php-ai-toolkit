<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Config\ConfigScalarReader;
use Toolkit\TreeGuard\Config\ConfigStringListReader;
use Toolkit\TreeGuard\Config\ReportConfig;
use Toolkit\TreeGuard\Config\ReportConfigReader;
use Toolkit\TreeGuard\TreeGuardException;

/**
 * @covers \Toolkit\TreeGuard\Config\ReportConfigReader
 * @uses \Toolkit\TreeGuard\Config\ConfigScalarReader
 * @uses \Toolkit\TreeGuard\Config\ConfigStringListReader
 * @uses \Toolkit\TreeGuard\Config\ReportConfig
 * @uses \Toolkit\TreeGuard\TreeGuardException
 */
#[CoversClass(ReportConfigReader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(TreeGuardException::class)]
final class ReportConfigReaderTest extends TestCase
{
    public function testReadAppliesDefaults(): void
    {
        $config = (new ReportConfigReader())->read([]);

        self::assertSame('ai', $config->reporter);
        self::assertSame(['path', 'rule'], $config->orderBy);
    }

    public function testReadParsesValues(): void
    {
        $config = (new ReportConfigReader())->read(['reporter' => 'json', 'order_by' => ['limit', 'actual']]);

        self::assertSame('json', $config->reporter);
        self::assertSame(['limit', 'actual'], $config->orderBy);
    }

    public function testReadRejectsNonMapping(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "report" must be a mapping.');

        (new ReportConfigReader())->read('text');
    }

    public function testReadRejectsUnknownReporter(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "report.reporter" must be one of: ai, text, json.');

        (new ReportConfigReader())->read(['reporter' => 'xml']);
    }

    public function testReadRejectsUnknownOrderField(): void
    {
        $this->expectException(TreeGuardException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "report.order_by" contains unsupported field "line".');

        (new ReportConfigReader())->read(['order_by' => ['line']]);
    }
}
