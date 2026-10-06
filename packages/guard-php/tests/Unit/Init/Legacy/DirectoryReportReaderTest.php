<?php

declare(strict_types=1);

namespace Tests\Unit\Init\Legacy;

use Guard\Config\Validation\DirectoryConfigScalarReader;
use Guard\Config\Validation\DirectoryConfigStringListReader;
use Guard\Init\Legacy\DirectoryReportConfig;
use Guard\Init\Legacy\DirectoryReportReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Init\Legacy\DirectoryReportReader
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Validation\DirectoryConfigScalarReader
 * @uses \Guard\Config\Validation\DirectoryConfigStringListReader
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Init\Legacy\DirectoryReportConfig
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(DirectoryReportReader::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(DirectoryConfigScalarReader::class)]
#[UsesClass(DirectoryConfigStringListReader::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(DirectoryReportConfig::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class DirectoryReportReaderTest extends TestCase
{
    public function testReadAppliesDefaults(): void
    {
        $config = (new DirectoryReportReader())->read([]);

        self::assertSame('ai', $config->reporter);
        self::assertSame(['path', 'rule'], $config->orderBy);
    }

    public function testReadParsesValues(): void
    {
        $config = (new DirectoryReportReader())->read(['reporter' => 'json', 'order_by' => ['limit', 'actual']]);

        self::assertSame('json', $config->reporter);
        self::assertSame(['limit', 'actual'], $config->orderBy);
    }

    public function testReadRejectsNonMapping(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "report" must be a mapping.');

        (new DirectoryReportReader())->read('text');
    }

    public function testReadRejectsUnknownReporter(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "report.reporter" must be one of: ai, text, json.');

        (new DirectoryReportReader())->read(['reporter' => 'xml']);
    }

    public function testReadRejectsUnknownOrderField(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid tree.yaml: "report.order_by" contains unsupported field "line".');

        (new DirectoryReportReader())->read(['order_by' => ['line']]);
    }
}
