<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Validation;

use Guard\Config\Validation\HeadingConfigStringListReader;
use Guard\Diagnostic\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Validation\HeadingConfigStringListReader
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(HeadingConfigStringListReader::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
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
