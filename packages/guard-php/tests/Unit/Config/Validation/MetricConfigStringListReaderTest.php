<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Validation;

use Guard\Config\Configuration;
use Guard\Config\Validation\MetricConfigStringListReader;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\DirectoryListing;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Context;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Validation\MetricConfigStringListReader
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
#[CoversClass(MetricConfigStringListReader::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
final class MetricConfigStringListReaderTest extends TestCase
{
    public function testReadReturnsConfiguredStringList(): void
    {
        self::assertSame(['src'], (new MetricConfigStringListReader())->read(['paths' => ['src']], 'paths', []));
    }

    public function testReadRejectsInvalidStringList(): void
    {
        $this->expectException(PolicyException::class);

        (new MetricConfigStringListReader())->read(['paths' => [1]], 'paths', []);
    }

    public function testReadRequiredRejectsEmptyList(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('must contain at least one entry');

        (new MetricConfigStringListReader())->readRequired(['roots' => []], 'roots', 'scan', false);
    }
}
