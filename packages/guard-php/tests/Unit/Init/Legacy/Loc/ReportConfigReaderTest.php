<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy\Loc;

use Guard\Config\Loc\ConfigKeyValidator;
use Guard\Config\Loc\ConfigScalarReader;
use Guard\Config\Loc\ConfigStringListReader;
use Guard\Init\Legacy\Loc\ReportConfig;
use Guard\Init\Legacy\Loc\ReportConfigReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\Loc\ReportConfigReader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Loc\ConfigKeyValidator
 * @uses \Guard\Config\Loc\ConfigScalarReader
 * @uses \Guard\Config\Loc\ConfigStringListReader
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Init\Legacy\Loc\ReportConfig
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ReportConfigReader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ConfigKeyValidator::class)]
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
    public function testReadReturnsReportConfig(): void
    {
        $report = (new ReportConfigReader())->read(['reporter' => 'json', 'order_by' => ['rule']]);

        self::assertSame('json', $report->reporter);
        self::assertSame(['rule'], $report->orderBy);
    }

    public function testReadRejectsUnsupportedReporter(): void
    {
        $this->expectException(PolicyException::class);

        (new ReportConfigReader())->read(['reporter' => 'xml']);
    }

    public function testReadRejectsUnsupportedOrderField(): void
    {
        $this->expectException(PolicyException::class);

        (new ReportConfigReader())->read(['order_by' => ['severity']]);
    }
}
