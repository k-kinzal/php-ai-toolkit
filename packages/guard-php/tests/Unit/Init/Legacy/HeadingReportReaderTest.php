<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy;

use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Config\Validation\HeadingConfigStringListReader;
use Guard\Init\Legacy\HeadingReportConfig;
use Guard\Init\Legacy\HeadingReportReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\HeadingReportReader
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Validation\HeadingConfigKeyValidator
 * @uses \Guard\Config\Validation\HeadingConfigStringListReader
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Init\Legacy\HeadingReportConfig
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(HeadingReportReader::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(HeadingConfigKeyValidator::class)]
#[UsesClass(HeadingConfigStringListReader::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(HeadingReportConfig::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class HeadingReportReaderTest extends TestCase
{
    public function testReadAppliesDefaults(): void
    {
        $config = (new HeadingReportReader())->read([]);

        self::assertSame('ai', $config->reporter);
        self::assertSame(['path', 'line', 'rule'], $config->orderBy);
    }

    public function testReadParsesReporterAndOrder(): void
    {
        $config = (new HeadingReportReader())->read(['reporter' => 'text', 'order_by' => ['rule']]);

        self::assertSame('text', $config->reporter);
        self::assertSame(['rule'], $config->orderBy);
    }

    public function testReadRejectsNonMapping(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "report" must be a mapping.');

        (new HeadingReportReader())->read('ai');
    }

    public function testReadRejectsUnknownReporter(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"report.reporter" must be one of: ai, text, json.');

        (new HeadingReportReader())->read(['reporter' => 'xml']);
    }

    public function testReadRejectsUnknownOrderField(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"report.order_by" contains unsupported field "limit". Supported fields: path, line, rule.');

        (new HeadingReportReader())->read(['order_by' => ['limit']]);
    }

    public function testReadRejectsUnknownKey(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('"report" contains unsupported key "orderBy"');

        (new HeadingReportReader())->read(['orderBy' => ['path']]);
    }
}
