<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Project\Legacy;

use Guard\Config\Project\Legacy\HeadingReportConfig;
use Guard\Config\Project\Legacy\HeadingReportReader;
use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Config\Validation\HeadingConfigStringListReader;
use Guard\Diagnostic\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Project\Legacy\HeadingReportReader
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Validation\HeadingConfigKeyValidator
 * @uses \Guard\Config\Validation\HeadingConfigStringListReader
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Config\Project\Legacy\HeadingReportConfig
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(HeadingReportReader::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(HeadingConfigKeyValidator::class)]
#[UsesClass(HeadingConfigStringListReader::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(HeadingReportConfig::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
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
