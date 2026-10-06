<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Validation;

use Guard\Config\Validation\HeadingConfigStringListReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Validation\HeadingConfigStringListReader
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(HeadingConfigStringListReader::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class HeadingConfigStringListReaderTest extends TestCase
{
    public function testReadReturnsStringsOrDefault(): void
    {
        self::assertSame(['*.md', 'docs/**/*.md'], (new HeadingConfigStringListReader())->read(['scan' => ['*.md', 'docs/**/*.md']], 'scan', [], ''));
        self::assertSame(['path'], (new HeadingConfigStringListReader())->read([], 'order_by', ['path'], 'report'));
    }

    public function testReadRejectsScalar(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "scan" must be a list of strings.');

        (new HeadingConfigStringListReader())->read(['scan' => '*.md'], 'scan', [], '');
    }

    public function testReadRejectsEmptyEntries(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid doc-guard.yaml: "report.order_by" must be a list of strings.');

        (new HeadingConfigStringListReader())->read(['order_by' => ['path', '']], 'order_by', [], 'report');
    }
}
