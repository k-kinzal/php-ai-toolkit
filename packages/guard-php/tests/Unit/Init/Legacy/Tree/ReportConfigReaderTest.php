<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy\Tree;

use Guard\Config\Tree\ConfigScalarReader;
use Guard\Config\Tree\ConfigStringListReader;
use Guard\Init\Legacy\Tree\ReportConfig;
use Guard\Init\Legacy\Tree\ReportConfigReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\Tree\ReportConfigReader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Tree\ConfigScalarReader
 * @uses \Guard\Config\Tree\ConfigStringListReader
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Init\Legacy\Tree\ReportConfig
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ReportConfigReader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ConfigScalarReader::class)]
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
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "report" must be a mapping.');

        (new ReportConfigReader())->read('text');
    }

    public function testReadRejectsUnknownReporter(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "report.reporter" must be one of: ai, text, json.');

        (new ReportConfigReader())->read(['reporter' => 'xml']);
    }

    public function testReadRejectsUnknownOrderField(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "report.order_by" contains unsupported field "line".');

        (new ReportConfigReader())->read(['order_by' => ['line']]);
    }
}
