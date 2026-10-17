<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Validation;

use Guard\Config\Configuration;
use Guard\Config\Validation\MetricConfigScalarReader;
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
 * @covers \Guard\Config\Validation\MetricConfigScalarReader
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
#[CoversClass(MetricConfigScalarReader::class)]
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
final class MetricConfigScalarReaderTest extends TestCase
{
    public function testStringReturnsNonEmptyString(): void
    {
        self::assertSame('ai', (new MetricConfigScalarReader())->string(['reporter' => 'ai'], 'reporter', 'text'));
    }

    public function testNullablePositiveIntReturnsPositiveIntegerAndNull(): void
    {
        $reader = new MetricConfigScalarReader();

        self::assertSame(10, $reader->nullablePositiveInt(['lines' => 10], 'lines', 'limits.file'));
        self::assertNull($reader->nullablePositiveInt(['lines' => null], 'lines', 'limits.file'));
    }

    public function testStringRejectsEmptyString(): void
    {
        $this->expectException(PolicyException::class);

        (new MetricConfigScalarReader())->string(['reporter' => ''], 'reporter', 'ai');
    }

    public function testNullablePositiveIntRejectsNonPositiveInteger(): void
    {
        $this->expectException(PolicyException::class);

        (new MetricConfigScalarReader())->nullablePositiveInt(['lines' => 0], 'lines', 'limits.file');
    }

    public function testRequiredStringRejectsMissingValue(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('apply.default');

        (new MetricConfigScalarReader())->requiredString([], 'default', 'apply');
    }

    public function testOptionalStringReturnsConfiguredValueOrNull(): void
    {
        $reader = new MetricConfigScalarReader();

        self::assertSame('standard', $reader->optionalString(['extends' => 'standard'], 'extends', 'policies.native'));
        self::assertNull($reader->optionalString([], 'extends', 'policies.standard'));
    }
}
