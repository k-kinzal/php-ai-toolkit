<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy\Doc;

use Guard\Config\Doc\ConfigKeyValidator;
use Guard\Config\Doc\ConfigStringListReader;
use Guard\Init\Legacy\Doc\ReportConfig;
use Guard\Init\Legacy\Doc\ReportConfigReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\Doc\ReportConfigReader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Doc\ConfigKeyValidator
 * @uses \Guard\Config\Doc\ConfigStringListReader
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Init\Legacy\Doc\ReportConfig
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ReportConfigReader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
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
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "report" must be a mapping.');

        (new ReportConfigReader())->read('ai');
    }

    public function testReadRejectsUnknownReporter(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"report.reporter" must be one of: ai, text, json.');

        (new ReportConfigReader())->read(['reporter' => 'xml']);
    }

    public function testReadRejectsUnknownOrderField(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"report.order_by" contains unsupported field "limit". Supported fields: path, line, rule.');

        (new ReportConfigReader())->read(['order_by' => ['limit']]);
    }

    public function testReadRejectsUnknownKey(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"report" contains unsupported key "orderBy"');

        (new ReportConfigReader())->read(['orderBy' => ['path']]);
    }
}
